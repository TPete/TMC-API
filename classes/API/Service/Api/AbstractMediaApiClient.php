<?php

namespace TinyMediaCenter\API\Service\Api;

/**
 * Abstract base class for the media API clients.
 */
abstract class AbstractMediaApiClient
{
    protected $baseUrl;
    protected $defaultArgs;

    /**
     * AbstractMediaApiClient constructor.
     *
     * @param string $baseUrl
     * @param array  $defaultArgs
     */
    public function __construct($baseUrl, array $defaultArgs = [])
    {
        $this->baseUrl     = $baseUrl;
        $this->defaultArgs = $defaultArgs;
    }

    /**
     * @param string $url
     * @param array  $args
     * @param bool   $decodeJson
     *
     * @return mixed
     */
    protected function curlDownload($url, $args = [], $decodeJson = false)
    {
        $url = $this->baseUrl.$url;
        $args = array_merge($this->defaultArgs, $args);
        $argStr = http_build_query($args);

        if (!empty($argStr)) {
            $url .= "?".$argStr;
        }

        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($decodeJson) {
            $output = json_decode($output, true);
        }

        return $output;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function downloadImage($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);

        return $raw;
    }
}
