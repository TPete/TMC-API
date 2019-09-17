<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Model\MediaFileInfoModel;
use TinyMediaCenter\API\Model\Movie\CollectionModelInterface;
use TinyMediaCenter\API\Model\Movie\MovieModelInterface;
use TinyMediaCenter\API\Model\DBModel;
use TinyMediaCenter\API\Service\AbstractStore;

/**
 * Class MovieStoreDB
 */
class MovieStoreDB extends AbstractStore
{
    /**
     * MovieStoreDB constructor.
     *
     * @param DBModel $dbModel
     */
    public function __construct(DBModel $dbModel)
    {
        $tables = array("movies", "lists", "list_parts", "collections", "collection_parts");
        parent::__construct($dbModel, $tables);
    }

    /**
     * @param string $category
     * @param string $sort
     * @param string $order
     * @param string $filter
     * @param string $genres
     * @param int    $cnt
     * @param int    $offset
     *
     * @return array
     */
    public function getMovies($category, $sort, $order, $filter, $genres, $cnt, $offset)
    {
        $db = $this->connect();
        $sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.title_sort, mov.added_date, mov.release_date, mov.collection_id, ifnull(col.name, '') collection_name";
        $sqlCnt = "Select count(*) Cnt";
        $sql = "
				From movies mov
				Left Join (
				    Select *
				    From collections col
				    Where col.category = '".$category."'
				) col on mov.COLLECTION_ID = col.MOVIE_DB_ID
				Where mov.category = '".$category."'";

        if (strlen($genres) > 0) {
            $whereGenres = "";
            $genres = strtolower($genres);
            $genresArray = explode(",", $genres);
            foreach ($genresArray as $gen) {
                $whereGenres .= "and Lower(mov.GENRES) like '%".$gen."%' ";
            }
            $sql .= $whereGenres;
        }

        if (strlen($filter) > 0) {
            $whereTitle = "";
            $whereTitleSort = "";
            $whereOriginalTitle = "";
            $whereActors = "";
            $whereDirector = "";
            $filter = strtolower($filter);
            $filterArray = explode(" ", $filter);
            foreach ($filterArray as $fil) {
                $whereTitle .= "
						Lower(mov.TITLE) like '%".$fil."%' and ";
                $whereTitleSort .= "
						Lower(mov.TITLE_SORT) like '%".$fil."%' and ";
                $whereOriginalTitle .= "
						Lower(mov.ORIGINAL_TITLE) like '%".$fil."%' and ";
                $whereActors .= "
						Lower(mov.ACTORS) like '%".$fil."%' and ";
                $whereDirector .= "
						Lower(mov.DIRECTOR) like '%".$fil."%' and ";
            }
            $whereTitle = substr($whereTitle, 0, -4);
            $whereTitleSort = substr($whereTitleSort, 0, -4);
            $whereOriginalTitle = substr($whereOriginalTitle, 0, -4);
            $whereActors = substr($whereActors, 0, -4);
            $whereDirector = substr($whereDirector, 0, -4);

            $sqlCnt = $sqlCnt.$sql."
						and (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle."
							or ".$whereActors." or ".$whereDirector.")";

            $sqlAll = $sqlCols.", 1 sorter, levenshtein(mov.TITLE, '".$filter."') dist ".$sql." and ".$whereTitle;
            $sqlAll .= "
					 Union ";
            $sqlAll .= $sqlCols.", 2 sorter, levenshtein(mov.TITLE_SORT, '".$filter."') dist ".$sql." and ".$whereTitleSort." and NOT(".$whereTitle.")";
            $sqlAll .= "
					 Union ";
            $sqlAll .= $sqlCols.", 3 sorter, levenshtein(mov.ORIGINAL_TITLE, '".$filter."') dist ".$sql." and ".$whereOriginalTitle." and NOT(".$whereTitle." or ".$whereTitleSort.")";
            $sqlAll .= "
					 Union ";
            $sqlAll .= $sqlCols.", 4 sorter, levenshtein(mov.ACTORS, '".$filter."') dist ".$sql." and ".$whereActors." and NOT (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle.")";
            $sqlAll .= "
					 Union ";
            $sqlAll .= $sqlCols.", 5 sorter, levenshtein(mov.DIRECTOR, '".$filter."') dist ".$sql." and ".$whereDirector." and NOT (".$whereTitle." or ".$whereTitleSort." or ".$whereOriginalTitle." or ".$whereActors.")";
        } else {
            $sqlCnt = $sqlCnt.$sql;
            $sqlAll = $sqlCols.", 1 sorter, 1 dist ".$sql;
        }

        $stmt = $db->query($sqlCnt);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rowCount = $row["Cnt"];

        if ($rowCount > 0) {
            $sql = $sqlAll;
            if ($sort === "name") {
                $sql .= "
						Order by sorter, dist, TITLE_SORT ".$order;
            }
            if ($sort === "date") {
                $sql .= "
						Order by sorter, dist, ID ".$order;
            }
            if ($sort === "year") {
                $sql .= "
						Order by sorter, dist, RELEASE_DATE ".$order;
            }
            if ($cnt > -1) {
                $sql .= "
						Limit ".$offset.", ".$cnt;
            }

            $stmt = $db->query($sql);
            $list = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $row['genres'] = explode(',', $row['genres']);
                $row['actors'] = explode(',', str_replace('&nbsp;', ' ', $row['actors']));
                $list[] = $row;
            }

            return array("list" => $list, "cnt" => $rowCount);
        } else {
            return array("list" => array(), "cnt" => 0);
        }
    }

    /**
     * @param string $category
     * @param int    $collectionId
     * @param int    $cnt
     * @param int    $offset
     *
     * @return array
     */
    public function getMoviesForCollection($category, $collectionId, $cnt, $offset)
    {
        $db = $this->connect();
        $sqlCnt = "Select count(*) Cnt";
        $sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, mov.collection_id, col.name collection_name";
        $sql = "
				From collections col
				Join collection_parts parts on col.ID = parts.COLLECTION_ID
				Join movies mov on parts.MOVIE_ID = mov.MOVIE_DB_ID and col.CATEGORY = mov.CATEGORY
				Where col.MOVIE_DB_ID = :collectionId
				  and mov.CATEGORY = :category 
				Order By mov.RELEASE_DATE";

        $stmt = $db->prepare($sqlCnt.$sql);
        $stmt->bindValue(":collectionId", $collectionId, \PDO::PARAM_INT);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rowCount = $row["Cnt"];

        if ($rowCount > 0) {
            $sql = $sqlCols.$sql;
            if ($cnt > -1) {
                $sql .= "
						Limit ".$offset.", ".$cnt;
            }
            $stmt = $db->prepare($sql);
            $stmt->bindValue(":collectionId", $collectionId, \PDO::PARAM_INT);
            $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
            $stmt->execute();
            $list = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $row['genres'] = explode(',', $row['genres']);
                $row['actors'] = explode(',', str_replace('&nbsp;', ' ', $row['actors']));
                $list[] = $row;
            }

            return array("list" => $list, "cnt" => $rowCount);
        } else {
            return array("list" => array(), "cnt" => 0);
        }
    }

    /**
     * @param string $category
     * @param int    $listId
     * @param int    $cnt
     * @param int    $offset
     *
     * @return array
     */
    public function getMoviesForList($category, $listId, $cnt, $offset)
    {
        $db = $this->connect();
        $sqlCnt = "Select count(*) Cnt";
        $sqlCols = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
							mov.countries, mov.actors, mov.director, mov.info, mov.original_title";
        $sql = "
				FROM list_parts lp
				JOIN lists li on lp.LIST_ID = li.ID
				JOIN movies mov ON lp.MOVIE_ID = mov.MOVIE_DB_ID and li.CATEGORY = mov.CATEGORY
				WHERE lp.LIST_ID = :listId
				  and mov.CATEGORY = :category 
				Order By mov.RELEASE_DATE";
        $stmt = $db->prepare($sqlCnt.$sql);
        $stmt->bindValue(":listId", $listId, \PDO::PARAM_INT);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $rowCount = $row["Cnt"];

        if ($rowCount > 0) {
            $sql = $sqlCols.$sql;
            if ($cnt > -1) {
                $sql .= "
						Limit ".$offset.", ".$cnt;
            }
            $stmt = $db->prepare($sql);
            $stmt->bindValue(":listId", $listId, \PDO::PARAM_INT);
            $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
            $stmt->execute();
            $list = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $list[] = $row;
            }

            return array("list" => $list, "cnt" => $rowCount);
        } else {
            return array("list" => array(), "cnt" => 0);
        }
    }

    /**
     * @param string $category
     * @param int    $id
     *
     * @return array
     */
    public function getMovieById($category, $id)
    {
        $sql = "Select mov.id, mov.movie_db_id, mov.title, mov.filename, mov.overview, mov.release_date, mov.genres,
						mov.countries, mov.actors, mov.director, mov.info, mov.original_title, 
						mov.collection_id, ifnull(col.name, '') collection_name
				From movies mov
				Left Join collections col on mov.COLLECTION_ID = col.MOVIE_DB_ID
				Where mov.category = :category and mov.id = :id";
        $sqlLists = "Select li.ID list_id, li.NAME list_name
					From movies mov
					Join list_parts lp on mov.MOVIE_DB_ID = lp.MOVIE_ID	
					Join lists li on li.ID = lp.LIST_ID
					Where mov.category = :category and mov.ID = :id";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare($sqlLists);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":id", $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row["lists"] = [];

        while ($tmp = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $row["lists"][] = ["list_id" => $tmp["list_id"], "list_name" => $tmp["list_name"]];
        }

        return $row;
    }

    /**
     * @param string              $category
     * @param MovieModelInterface $movie
     * @param MediaFileInfoModel  $mediaFileInfoModel
     * @param string              $dir
     * @param string              $filename
     * @param string              $id
     *
     * @throws \Exception
     */
    public function updateMovie($category, MovieModelInterface $movie, MediaFileInfoModel $mediaFileInfoModel, $dir, $filename, $id = "")
    {
        $db = $this->connect();

        if ($id === "") {
            $id = $this->getIdByCategoryAndFilename($category, $filename);
        }

        if (empty($id)) {
            $sql = "Insert into movies(MOVIE_DB_ID, TITLE, FILENAME, OVERVIEW, RELEASE_DATE, GENRES, 
					COUNTRIES, ACTORS, DIRECTOR, INFO, ORIGINAL_TITLE, COLLECTION_ID, ADDED_DATE, TITLE_SORT, CATEGORY,
					DURATION, RESOLUTION, SOUND)
					Values (:movieDBId, :title, :filename, :overview, :releaseDate, :genres, 
					:countries, :actors, :director, :info, :originalTitle, :collectionId, :addedDate, :titleSort, :category,
					:duration, :resolution, :sound)";
        } else {
            $sql = "Update movies
					set MOVIE_DB_ID = :movieDBId, TITLE = :title, FILENAME = :filename, OVERVIEW = :overview, 
					RELEASE_DATE = :releaseDate, GENRES = :genres, COUNTRIES = :countries, ACTORS = :actors,  
					DIRECTOR = :director, INFO = :info, ORIGINAL_TITLE = :originalTitle, COLLECTION_ID = :collectionId, 
					ADDED_DATE = :addedDate, TITLE_SORT = :titleSort, CATEGORY = :category, DURATION = :duration,
					RESOLUTION = :resolution, SOUND = :sound
					Where ID = ".$id;
        }

        $actors = array_slice($movie->getActors(), 0, 10);
        $actors = implode(',', $actors);
        $directors = implode(',', $movie->getDirectors());
        $countries = implode(',', $movie->getCountries());
        $genres = implode(',', $movie->getGenres());

        $added = $this->getFiletime($dir.$filename);
        $titleSort = $movie->getTitle(); //TODO ???
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":movieDBId", $movie->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(":title", $movie->getTitle(), \PDO::PARAM_STR);
        $stmt->bindValue(":filename", $filename, \PDO::PARAM_STR);
        $stmt->bindValue(":overview", $movie->getOverview(), \PDO::PARAM_STR);
        $stmt->bindValue(":releaseDate", $movie->getReleaseDate()->format('Y-m-d'), \PDO::PARAM_STR);
        $stmt->bindValue(":genres", $genres, \PDO::PARAM_STR);
        $stmt->bindValue(":countries", $countries, \PDO::PARAM_STR);
        $stmt->bindValue(":actors", $actors, \PDO::PARAM_STR);
        $stmt->bindValue(":director", $directors, \PDO::PARAM_STR);
        $stmt->bindValue(":info", $mediaFileInfoModel->getAsString(), \PDO::PARAM_STR);
        $stmt->bindValue(":originalTitle", $movie->getOriginalTitle(), \PDO::PARAM_STR);
        $stmt->bindValue(":collectionId", $movie->getCollectionId(), \PDO::PARAM_INT);
        $stmt->bindValue(":addedDate", $added, \PDO::PARAM_STR);
        $stmt->bindValue(":titleSort", $titleSort, \PDO::PARAM_STR);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->bindValue(":duration", $mediaFileInfoModel->getDuration(), \PDO::PARAM_STR);
        $stmt->bindValue(":resolution", $mediaFileInfoModel->getResolution(), \PDO::PARAM_STR);
        $stmt->bindValue(":sound", $mediaFileInfoModel->getSound(), \PDO::PARAM_STR);
        $stmt->execute();
    }
    /**
     * @param string                   $category
     * @param CollectionModelInterface $collectionModel
     * @param int                      $id
     */
    public function updateCollectionById($category, $collectionModel, $id)
    {
        $sql = "Select ID, MOVIE_DB_ID
				From collections
				Where MOVIE_DB_ID = :id
				  And CATEGORY = :category";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":id", $id, \PDO::PARAM_INT);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            $sql = "Insert into collections(MOVIE_DB_ID, NAME, OVERVIEW, CATEGORY)
				Values (:movieDBId, :name, :overview, :category)";
        } else {
            $sqlOld = "Delete
						From collection_parts
						Where COLLECTION_ID = ".$row["ID"];
            $db->query($sqlOld);
            $sql = "Update collections
					set MOVIE_DB_ID = :movieDBId,
					NAME = :name,
					OVERVIEW = :overview,
					CATEGORY = :category
					Where ID = ".$row["ID"];
        }
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":movieDBId", $collectionModel->getId(), \PDO::PARAM_INT);
        $stmt->bindValue(":name", $collectionModel->getName(), \PDO::PARAM_STR);
        $stmt->bindValue(":overview", $collectionModel->getOverview(), \PDO::PARAM_STR);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();

        if ($row === false) {
            $id = $db->lastInsertId();
        } else {
            $id = $row["ID"];
        }

        $sqlParts = "Insert into collection_parts(COLLECTION_ID, MOVIE_ID)
					Values (:collectionId, :movieId)";
        $stmtParts = $db->prepare($sqlParts);

        foreach ($collectionModel->getParts() as $part) {
            $stmtParts->bindValue(":collectionId", $id, \PDO::PARAM_INT);
            $stmtParts->bindValue(":movieId", $part["id"], \PDO::PARAM_INT);
            $stmtParts->execute();
        }
    }

    /**
     * @param int $collectionId
     */
    public function removeObsoleteCollection($collectionId)
    {
        $sql = "Delete
				From collections
				Where id = :collectionId";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":collectionId", $collectionId);
        $stmt->execute();
    }

    /**
     * @param string $category
     * @param int    $dir
     *
     * @return string
     */
    public function checkRemovedFiles($category, $dir)
    {
        $sql = "Select ID, MOVIE_DB_ID, FILENAME
				From movies
				Where CATEGORY = :category
				Order by id";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $list = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!file_exists($dir.$row["FILENAME"])) {
                $list[] = $row;
            }
        }
        $sql = "Delete From movies
				Where ID = :id";
        $stmt = $db->prepare($sql);
        $protocol = "";
        foreach ($list as $toRemove) {
            $protocol .= "Removing ".$toRemove["FILENAME"]."<br>";
            $stmt->bindValue(":id", $toRemove["ID"], \PDO::PARAM_INT);
            $stmt->execute();
        }

        return $protocol;
    }

    /**
     * Returns the files, which are not yet present in the database.
     *
     * @param string $category
     * @param string $dir
     *
     * @return array
     */
    public function checkExisting($category, $dir)
    {
        $db = $this->connect();
        $sql = "Select count(*) cnt
				From movies
				Where FILENAME = :filename
				  and CATEGORY = :category";
        $stmt = $db->prepare($sql);
        $files = glob($dir."*.avi");
        $missing = [];

        foreach ($files as $file) {
            $filename = substr($file, strrpos($file, "/") + 1);
            $stmt->bindValue(":filename", $filename, \PDO::PARAM_STR);
            $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $cnt = intval($row["cnt"], 10);

            if ($cnt === 0) {
                $missing[] = $filename;
            }
        }

        return $missing;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function checkDuplicates($category)
    {
        $db = $this->connect();
        $sql = "Select Title
				From movies
				Where CATEGORY = :category
				Group By Title
				Having count(*) > 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $duplicates = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $duplicates[] = $row["Title"];
        }

        return $duplicates;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function checkCollections($category)
    {
        $db = $this->connect();
        $sql = "SELECT mov.collection_id id
				FROM movies mov
				LEFT JOIN collections col ON mov.collection_id = col.movie_db_id AND mov.CATEGORY = col.CATEGORY
				WHERE mov.collection_id IS NOT NULL
				AND col.name IS NULL 
				AND mov.CATEGORY = :category";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $missing = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $missing[] = $row["id"];
        }

        $sql = "SELECT col.id As id
				FROM collections col 
				LEFT JOIN movies mov ON mov.collection_id = col.movie_db_id AND mov.CATEGORY = col.CATEGORY
				WHERE col.id IS NOT NULL
				AND mov.collection_id IS NULL 
				AND col.CATEGORY = :category";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $obsolete = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $obsolete[] = $row["id"];
        }

        return ["missing" => $missing, "obsolete" => $obsolete];
    }

    /**
     * @param string $category
     * @param string $dir
     *
     * @return array
     */
    public function getMissingPics($category, $dir)
    {
        $sql = "Select ID, MOVIE_DB_ID
				From movies
				Where CATEGORY = :category
				Order by id";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $movieDBIDS = [];
        $missing = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $movieDBIDS[] = $row["MOVIE_DB_ID"];
            $big = $dir.$row["MOVIE_DB_ID"]."_big.jpg";

            if (!file_exists($big)) {
                $missing[] = $row;
            }
        }

        return ["missing" => $missing, "all" => $movieDBIDS];
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getGenres($category)
    {
        $sql = "Select genres
				From movies
				Where category = :category";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $genres = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tmp = explode(",", $row["genres"]);
            foreach ($tmp as $val) {
                if (!in_array($val, $genres)) {
                    $genres[] = $val;
                }
            }
        }
        sort($genres);

        return $genres;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getLists($category)
    {
        $sql = "SELECT id, name
				FROM lists
				Where category = :category
				ORDER BY name";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $lists = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $lists;
    }

    /**
     * @param string $category
     *
     * @return array
     */
    public function getCollections($category)
    {
        $sql = "SELECT MOVIE_DB_ID id, name
				FROM collections
				Where category = :category
				ORDER BY name";
        $db = $this->connect();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $collections = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $collections;
    }

    /**
     * @param string $category
     * @param string $filename
     *
     * @throws \Exception
     *
     * @return int
     */
    private function getIdByCategoryAndFilename($category, $filename)
    {
        $db = $this->connect();

        $sql = "Select ID
					From movies
					Where FILENAME = :filename
					and CATEGORY = :category";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":filename", $filename, \PDO::PARAM_STR);
        $stmt->bindValue(":category", $category, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            throw new \Exception('Error getting movie id');
        }

        return $row["ID"];
    }

    private function getFiletime($path)
    {
        $fileTime = date("Y-m-d", filemtime($path));

        return $fileTime;
    }
}
