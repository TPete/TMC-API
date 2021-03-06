<?php

namespace TinyMediaCenter\API;

/**
 * Class ScrapeException
 */
class ScrapeException extends \Exception
{
    /**
     * ScrapeException constructor.
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
        $this->message = $message;
    }
}
