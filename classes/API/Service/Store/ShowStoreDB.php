<?php
namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Exception\NotFoundException;
use TinyMediaCenter\API\Model\DBModel;
use TinyMediaCenter\API\Service\AbstractStore;

/**
 * Class ShowStoreDB
 */
class ShowStoreDB extends AbstractStore
{
    /**
     * ShowStoreDB constructor.
     *
     * @param DBModel $dbModel
     */
    public function __construct(DBModel $dbModel)
    {
        $tables = array("shows", "show_episodes");
        parent::__construct($dbModel, $tables);
    }

    /**
     * @param string $category
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public function getShows($category)
    {
        $db = $this->connect();
        $sql = "Select id, title, folder, tvdb_id, ordering_scheme, lang
				From shows
				Where category = :category 
				order by title";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $shows = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $shows[] = $row;
        }

        if (empty($shows)) {
            throw new NotFoundException(sprintf('Category "%s" not found', $category));
        }

        return $shows;
    }

    /**
     * //TODO add model for store return, use also for getShows
     *
     * Get the series details if available, null otherwise.
     *
     * @param string $category
     * @param string $folder
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public function getShowDetails($category, $folder)
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

        return $seriesDetails;
    }

    /**
     * @param string $category
     * @param string $folder
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public function getEpisodes($category, $folder)
    {
        $db = $this->connect();
        $sql = "Select ep.season_no, ep.episode_no, ep.title, ep.id, ep.description
				From shows sh
				Join show_episodes ep on sh.id = ep.show_id
				Where sh.category = :category and sh.folder = :folder 
				Order by ep.season_no, ep.episode_no";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();
        $episodes = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $episodes[] = $row;
        }

        if (empty($episodes)) {
            throw new NotFoundException(sprintf('Episodes for "%s/%s" not found', $category, $folder));
        }

        return $episodes;
    }

    /**
     * TODO obsolete, remove
     *
     * @param string $category
     * @param string $folder
     * @param string $episode
     *
     * @throws NotFoundException
     *
     * @return array
     */
    public function getEpisode($category, $folder, $episode)
    {
        $db = $this->connect();
        $sql = "Select ep.season_no, ep.episode_no, ep.title, ep.id, ep.description
				From shows sh
				Join show_episodes ep on sh.id = ep.show_id
				Where sh.category = :category and sh.folder = :folder and ep.id = :episode
				Order by ep.season_no, ep.episode_no";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->bindValue(":episode", $episode, \PDO::PARAM_STR);
        $stmt->execute();

        $episodeDetails = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($episodeDetails)) {
            throw new NotFoundException(sprintf('Episode for "%s/%s/%s" not found', $category, $folder, $episode));
        }

        return $episodeDetails;
    }

    /**
     * TODO obsolete, remove
     *
     * @param string $category
     * @param int    $id
     *
     * @return mixed
     */
    public function getEpisodeDescription($category, $id)
    {
        $db = $this->connect();
        $sql = "Select concat( season_no, 'x', lpad(episode_no, 2, '0')) episode, title, description
				From show_episodes
				Where id = :id
				order by title";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":id", $id, \PDO::PARAM_STR);
        $stmt->execute();
        $desc = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $desc;
    }

    /**
     * Update the title and the web database id of a show.
     *
     * Return the old web database id.
     *
     * @param String $category The category name.
     * @param String $folder   The folder name.
     * @param String $title    The new show title.
     * @param int    $tvdbId   The new web database id.
     * @param String $lang     The language
     *
     * @return int The old web database id.
     */
    public function updateDetails($category, $folder, $title, $tvdbId, $lang)
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
        $stmt->bindValue(":tvdb_id", $tvdbId, \PDO::PARAM_INT);
        $stmt->bindValue(":lang", $lang);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":folder", $folder, \PDO::PARAM_STR);
        $stmt->execute();

        return $row["TVDB_ID"];
    }

    /**
     * @param string $category
     * @param string $folder
     * @param string $title
     *
     * @return string|null
     */
    public function createIfMissing($category, $folder, $title)
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
     * @param string $category
     * @param array  $folders
     *
     * @return array
     */
    public function removeIfObsolete($category, $folders)
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
        $protocol = [];

        foreach ($dbFolders as $row) {
            if (!in_array($row["folder"], $folders)) {
                $stmtShows->bindValue(":folder", $row["folder"], \PDO::PARAM_STR);
                $stmtShows->execute();
                $protocol[] = $row["folder"];
            }
        }

        return $protocol;
    }

    /**
     * @param int   $showId
     * @param array $seasons
     */
    public function updateEpisodes($showId, $seasons)
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
        $seasonCnt = 1;
        foreach ($seasons as $episodes) {
            $episodeCnt = 1;
            foreach ($episodes as $episode) {
                $stmtInsert->bindValue(":showId", $showId, \PDO::PARAM_INT);
                $stmtInsert->bindValue(":seasonNo", $seasonCnt, \PDO::PARAM_INT);
                $stmtInsert->bindValue(":episodeNo", $episodeCnt, \PDO::PARAM_INT);
                $stmtInsert->bindValue(":title", $episode["title"], \PDO::PARAM_STR);
                $stmtInsert->bindValue(":description", $episode["description"], \PDO::PARAM_STR);
                $stmtInsert->execute();
                $episodeCnt++;
            }
            $seasonCnt++;
        }
    }
}
