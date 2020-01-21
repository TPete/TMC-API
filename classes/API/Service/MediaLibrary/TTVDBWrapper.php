<?php

namespace TinyMediaCenter\API\Service\MediaLibrary;

use TinyMediaCenter\API\Exception\ScrapeException;
use TinyMediaCenter\API\Service\AbstractDBAPIWrapper;

/**
 * Class TTVDBWrapper
 */
class TTVDBWrapper extends AbstractDBAPIWrapper
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $imageBaseUrl = "https://thetvdb.com/banners/fanart/original/";

    /**
     * TTVDBWrapper constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        parent::__construct("https://thetvdb.com/api/");
        $this->apiKey = $apiKey;
        libxml_use_internal_errors(true);
    }

    /**
     * @param string $name
     *
     * @throws ScrapeException
     *
     * @return string
     */
    public function getSeriesId($name)
    {
        $url = "GetSeries.php?language=de&seriesname=".$name;
        $raw = $this->curlDownload($url);
        if (strlen($raw) === 0) {
            throw new ScrapeException("Failed to retrieve series id: Web API returned no data.");
        }
        try {
            $xml = new \SimpleXMLElement($raw);
            if (!empty($xml->Series[0])) {
                $id = $xml->Series[0]->id;

                return (string) $id;
            }
        } catch (\Exception $e) {
            throw new ScrapeException("Failed to retrieve series id: ".$e->getMessage());
        }
        throw new ScrapeException("Failed to retrieve series id");
    }

    /**
     * @param int    $id
     * @param string $orderingScheme
     * @param string $lang
     *
     * @throws ScrapeException
     *
     * @return array
     */
    public function getSeriesInfoById($id, $orderingScheme, $lang = 'de')
    {
        $url = $this->apiKey."/series/".$id."/all/".$lang.".xml";
        $raw = $this->curlDownload($url);

        try {
            if (strlen($raw) === 0) {
                throw new ScrapeException("Failed to retrieve series info for id ".$id.": Web API returned no data.");
            }

            $xml = new \SimpleXMLElement($raw);
            $rawEpisodes = $xml->Episode;
            $seasons = [];
            $seasonNumber = 0;
            $episodeNumber = 0;

            foreach ($rawEpisodes as $re) {
                if ($orderingScheme === "DVD") {
                    $seasonNumber = (int) (strlen($re->DVD_season) > 0 ? $re->DVD_season : $re->SeasonNumber);
                }
                if ($orderingScheme === "Aired") {
                    $seasonNumber = (int) $re->SeasonNumber;
                }

                if ($seasonNumber === 0) {//skip specials
                    continue;
                }
                if (!isset($seasons[$seasonNumber])) {
                    $seasons[$seasonNumber] = array();
                }
                if ($orderingScheme === "DVD") {
                    $episodeNumber = (int) (strlen($re->DVD_episodenumber) > 0 ? $re->DVD_episodenumber : $re->EpisodeNumber);
                }
                if ($orderingScheme === "Aired") {
                    $episodeNumber = (int) $re->EpisodeNumber;
                }
                $seasons[$seasonNumber][$episodeNumber] = ["title" => (string) $re->EpisodeName, "description" => (string) $re->Overview];
            }

            ksort($seasons);

            foreach ($seasons as &$season) {
                ksort($season);
            }

            if (empty($seasons)) {
                throw new ScrapeException('Scraping failed (check ID): No data');
            }

            return $seasons;
        } catch (\Exception $e) {
            throw new ScrapeException("Failed to retrieve series info for id ".$id);
        }
    }

    /**
     * TODO obsolete, remove
     *
     * @param string $name
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getSeriesInfoByName($name)
    {
        $id = $this->getSeriesId($name);

        return $this->getSeriesInfoById($id);
    }

    /**
     * @param string $seriesId
     * @param string $path
     */
    public function downloadBG($seriesId, $path)
    {
        $url = $this->imageBaseUrl.$seriesId."-1.jpg";
        $this->downloadImage($url, $path);
    }
}
