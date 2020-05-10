<?php

namespace TinyMediaCenter\API\Exception;

/**
 * Class MediaApiClientException
 */
class MediaApiClientException extends \Exception
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
