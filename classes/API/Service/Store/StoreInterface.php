<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Model\DBModel;

/**
 * A store.
 */
interface StoreInterface
{
    /**
     * Check the store setup.
     *
     * @param DBModel $dbModel
     *
     * @return bool
     */
    public function checkSetup(DBModel $dbModel = null);

    /**
     * Setup the store.
     */
    public function setup();
}
