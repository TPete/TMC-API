<?php

namespace TinyMediaCenter\API\Service\Store;

use TinyMediaCenter\API\Model\Database;

/**
 * A store.
 */
interface StoreInterface
{
    /**
     * Check the store setup.
     *
     * @param Database $dbModel
     *
     * @return bool
     */
    public function checkSetup(Database $dbModel = null);

    /**
     * Setup the store.
     */
    public function setup();
}
