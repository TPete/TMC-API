<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Model\Database;
use TinyMediaCenter\API\Model\SeriesInterface;
use TinyMediaCenter\API\Model\Store\Series;

/**
 * Store for series.
 *
 * TODO should be more consistent: use folder to identify new and id for existing entries.
 */
class SeriesStoreDB extends AbstractStore implements SeriesStoreInterface
{
    /**
     * SeriesStoreDB constructor.
     *
     * @param Database $dbModel
     */
    public function __construct(Database $dbModel)
    {
        $tables = array("shows", "show_episodes");
        parent::__construct($dbModel, $tables);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeries(string $category): array
    {
        $db = $this->connect();
        $sql = "Select id, title, folder, tvdb_id, ordering_scheme, lang
				From shows
				Where category = :category 
				order by title";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $series = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $series[] = $this->createModel($row);
        }

        if (empty($series)) {
            throw new NotFoundException(sprintf('Category "%s" not found', $category));
        }

        return $series;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeriesDetails(string $category, string $folder): Series
    {
        $db = $this->connect();
        $sql = "Select id, title, folder, tvdb_id, ordering_scheme, lang
				From shows
				Where category = :category and folder = :folder 
				order by title";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();
        $seriesDetails = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (false === $seriesDetails) {
            throw new NotFoundException(sprintf('Series "%s" not found', $folder));
        }

        return $this->createModel($seriesDetails);
    }

    /**
     * {@inheritDoc}
     */
    public function getSeasonCount(string $category, string $folder): int
    {
        $db = $this->connect();
        $sql = "Select max(ep.season_no)
                From shows sh
                Join show_episodes ep on sh.id = ep.show_id
                Where sh.category = :category 
				And sh.folder = :folder ";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_NUM);

        return (int) $row[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getEpisodes(string $category, string $folder, ?int $season = null): array
    {
        $db = $this->connect();
        $sql = "Select ep.season_no, ep.episode_no, ep.title, ep.id, ep.description
				From shows sh
				Join show_episodes ep on sh.id = ep.show_id
				Where sh.category = :category 
				And sh.folder = :folder ";

        if ($season !== null) {
            $sql .= " And ep.season_no = :season ";
        }

        $sql .= "Order by ep.season_no, ep.episode_no";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);

        if ($season !== null) {
            $stmt->bindValue(":season", $season, \PDO::PARAM_INT);
        }

        $stmt->execute();
        $episodes = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $episodes[] = new Series\Episode($row['episode_no'], $row['season_no'], $row['title'], $row['description']);
        }

        if (empty($episodes)) {
            throw new NotFoundException(sprintf('Episodes for "%s/%s/%s" not found', $category, $folder, $season));
        }

        return $episodes;
    }

    /**
     * {@inheritDoc}
     */
    public function updateDetails(string $category, string $folder, string $title, int $mediaApiId, string $lang): int
    {
        $db = $this->connect();
        $sql = "Select TVDB_ID
				From shows
				Where category = :category and folder = :folder";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $sql = "Update shows
				set Title = :title,
				TVDB_ID = :tvdb_id,
				Lang = :lang
				Where category = :category and folder = :folder";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":title", $title, \PDO::PARAM_STR);
        $stmt->bindValue(":tvdb_id", $mediaApiId, \PDO::PARAM_INT);
        $stmt->bindValue(":lang", $lang);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();

        return $row["TVDB_ID"]; //TODO why?
    }

    /**
     * {@inheritDoc}
     */
    public function createIfMissing(string $category, string $folder, string $title): ?string
    {
        $db = $this->connect();
        $sql = "Select count(*) cnt
				From shows
				Where category = :category and folder = :folder";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row["cnt"] === "0") {
            $sql = "Insert into shows(category, folder, title, ordering_scheme)
					Values (:category, :folder, :title, 'Aired')"; //TODO ordering_scheme
            $stmt = $db->prepare($sql);
            $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
            $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
            $stmt->bindValue(":title", $title, \PDO::PARAM_STR);
            $stmt->execute();

            return $folder;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function removeIfObsolete(string $category, array $folders): array
    {
        $db = $this->connect();
        $sql = "Select folder
				From shows
				Where category = :category";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $dbFolders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $sqlShows = "Delete 
				From shows
				Where category = :category and folder = :folder";
        $stmtShows = $db->prepare($sqlShows);
        $stmtShows->bindValue(":category", $category, \PDO::PARAM_STR);
        $removed = [];

        foreach ($dbFolders as $row) {
            if (!in_array($row["folder"], $folders)) {
                $stmtShows->bindValue(":folder", $row["folder"], \PDO::PARAM_STR);
                $stmtShows->execute();
                $removed[] = $row["folder"];
            }
        }

        return $removed;
    }

    /**
     * {@inheritDoc}
     */
    public function updateEpisodes(int $showId, SeriesInterface $series)
    {
        $sqlDelete = "Delete
					From show_episodes
					Where show_id = :showId";
        $sqlInsert = "Insert into show_episodes(show_id, season_no, episode_no, title, description)
					Values(:showId, :seasonNo, :episodeNo, :title, :description)";
        $db = $this->connect();
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(":showId", $showId);
        $stmtDelete->execute();
        $stmtInsert = $db->prepare($sqlInsert);

        foreach ($series->getSeasons() as $season) {
            foreach ($season->getEpisodes() as $episode) {
                $stmtInsert->bindValue(":showId", $showId, \PDO::PARAM_INT);
                $stmtInsert->bindValue(":seasonNo", $season->getNumber(), \PDO::PARAM_INT);
                $stmtInsert->bindValue(":episodeNo", $episode->getNumber(), \PDO::PARAM_INT);
                $stmtInsert->bindValue(":title", $episode->getTitle(), \PDO::PARAM_STR);
                $stmtInsert->bindValue(":description", $episode->getDescription(), \PDO::PARAM_STR);
                $stmtInsert->execute();
            }
        }
    }

    /**
     * @param array $seriesData
     *
     * @return Series
     */
    private function createModel(array $seriesData)
    {
        return new Series(
            $seriesData['id'],
            $seriesData['title'],
            $seriesData['folder'],
            $seriesData['tvdb_id'],
            $seriesData['lang'],
            $seriesData['ordering_scheme']
        );
    }
}
