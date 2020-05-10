<?php

namespace TinyMediaCenter\API\Service\Api\Series;

use TinyMediaCenter\API\Exception\MediaApiClientException;
use TinyMediaCenter\API\Model\Api\Series\Season;
use TinyMediaCenter\API\Model\Api\Series\TheTvDbModel;
use TinyMediaCenter\API\Model\SeriesInterface;
use TinyMediaCenter\API\Service\Api\AbstractMediaApiClient;
use TinyMediaCenter\API\Service\Api\SeriesApiClientInterface;

/**
 * Class TheTvDbApi
 */
class TheTvDbApiClient extends AbstractMediaApiClient implements SeriesApiClientInterface
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
     * TheTvDbApi constructor.
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
     * {@inheritDoc}
     */
    public function getSeriesId(string $name): string
    {
        $url = "GetSeries.php?language=de&seriesname=".$name;
        $raw = $this->curlDownload($url);
        if (strlen($raw) === 0) {
            throw new MediaApiClientException("Failed to retrieve series id: Web API returned no data.");
        }
        try {
            $xml = new \SimpleXMLElement($raw);
            if (!empty($xml->Series[0])) {
                $id = $xml->Series[0]->id;

                return (string) $id;
            } else {
                $id = $xml->Series->id;

                return (string) $id;
            }
        } catch (\Exception $e) {
            throw new MediaApiClientException("Failed to retrieve series id: ".$e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSeriesInfoById(string $id, string $orderingScheme, ?string $lang = 'de'): SeriesInterface
    {
        $url = $this->apiKey."/series/".$id."/all/".$lang.".xml";
        $raw = $this->curlDownload($url);

        try {
            if (strlen($raw) === 0) {
                throw new MediaApiClientException("Failed to retrieve series info for id ".$id.": Web API returned no data.");
            }

            $xml = new \SimpleXMLElement($raw);
            $rawEpisodes = $xml->Episode;

            $series = new TheTvDbModel();
//            $seasons = [];
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

                $season = $series->getSeason($seasonNumber);

                if ($season === null) {
                    $season = new Season($seasonNumber);
                    $series->addSeason($season);
                }

//                if (!isset($seasons[$seasonNumber])) {
//                    $seasons[$seasonNumber] = array();
//                }
                if ($orderingScheme === "DVD") {
                    $episodeNumber = (int) (strlen($re->DVD_episodenumber) > 0 ? $re->DVD_episodenumber : $re->EpisodeNumber);
                }

                if ($orderingScheme === "Aired") {
                    $episodeNumber = (int) $re->EpisodeNumber;
                }

//                $seasons[$seasonNumber][$episodeNumber] = ["title" => (string) $re->EpisodeName, "description" => (string) $re->Overview];

                $season->addEpisode(new Season\Episode($episodeNumber, (string) $re->EpisodeName, (string) $re->Overview));
            }

//            ksort($seasons);
//
//            foreach ($seasons as &$season) {
//                ksort($season);
//            }

            if (empty($series->getSeasons())) {
                throw new MediaApiClientException('Scraping failed (check ID): No data');
            }

            return $series;
        } catch (\Exception $e) {
            throw new MediaApiClientException("Failed to retrieve series info for id ".$id);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function downloadBackgroundImage(string $seriesId): string
    {
        $url = $this->imageBaseUrl.$seriesId."-1.jpg";

        return $this->downloadImage($url);
    }
}
