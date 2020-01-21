<?php

namespace TinyMediaCenter\API\Model\Resource\Area\Category;

use TinyMediaCenter\API\Model\AbstractResourceModel;

/**
 * A tv series.
 */
class SeriesModel extends AbstractResourceModel
{
    /**
     * {@inheritDoc}
     */
    protected $type = 'series';

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $language;

    /**
     * TODO really necessary?
     *
     * @var string
     */
    private $tvdbId;

    /**
     * TODO really necessary?
     *
     * @var string
     */
    private $folder;

    /**
     * SeriesModel constructor.
     *
     * @param string $id
     * @param string $title
     * @param string $image
     * @param string $language
     * @param string $tvdbId
     * @param string $folder
     */
    public function __construct($id, $title, $image, $language, $tvdbId, $folder)
    {
        //TODO seasons?
        parent::__construct($id);
        $this->title = $title;
        $this->image = $image;
        $this->language = $language;
        $this->tvdbId = $tvdbId;
        $this->folder = $folder;
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
    public function getImage()
    {
        return $this->image;
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
    public function getTvdbId()
    {
        return $this->tvdbId;
    }

    /**
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'attributes' => [
                    'title' => $this->getTitle(),
                    'image' => $this->getImage(),
                    'language' => $this->getLanguage(),
                    'tvdbId' => $this->getTvdbId(),
                    'folder' => $this->getFolder(),
                ],
            ]
        );
    }
}
