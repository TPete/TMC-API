<?php
namespace TinyMediaCenter\API;

/**
 * Class AbstractController
 */
abstract class AbstractController
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var AbstractStore
     */
    protected $store;

    /**
     * @var AbstractDBAPIWrapper
     */
    protected $scraper;

    /**
     * Controller constructor.
     *
     * @param string               $path
     * @param string               $alias
     * @param AbstractStore        $store
     * @param AbstractDBAPIWrapper $scraper
     */
    public function __construct($path, $alias, AbstractStore $store, AbstractDBAPIWrapper $scraper)
    {
        $this->path = $path;
        $this->alias = $alias;
        $this->store = $store;
        $this->scraper = $scraper;
    }

    /**
     * Get categories.
     */
    abstract public function getCategories();

    /**
     * Update data.
     */
    abstract public function updateData();
}
