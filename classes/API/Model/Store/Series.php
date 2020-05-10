<?php

namespace TinyMediaCenter\API\Model\Store;

/**
 * A TV series.
 */
class Series
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $apiId;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string
     */
    private $orderingScheme;

    /**
     * SeriesModel constructor.
     *
     * @param int    $id
     * @param string $title
     * @param string $folder
     * @param string $apiId
     * @param string $language
     * @param string $orderingScheme
     */
    public function __construct($id, $title, $folder, $apiId, $language, $orderingScheme)
    {
        $this->id = $id;
        $this->title = $title;
        $this->folder = $folder;
        $this->apiId = $apiId;
        $this->language = $language;
        $this->orderingScheme = $orderingScheme;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @return string
     */
    public function getApiId()
    {
        return $this->apiId;
    }

    /**
     * @param string $apiId
     */
    public function setApiId($apiId)
    {
        $this->apiId = $apiId;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getOrderingScheme()
    {
        return $this->orderingScheme;
    }
}
