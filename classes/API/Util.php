<?php

namespace TinyMediaCenter\API;

/**
 * Class Util
 */
class Util
{
    /**
     * @param array  $data
     * @param string $sort
     * @param string $order
     *
     * @return array
     */
    public static function sortFiles($data, $sort, $order)
    {
        if ($sort === "name") {
            sort($data, SORT_STRING);
            if ($order === "desc") {
                $data = array_reverse($data);
            }
        }
        if ($sort === "date") {
            if ($order === "asc") {
                usort($data, function ($a, $b) {
                    return filemtime($a) > filemtime($b);
                });
            } else {
                usort($data, function ($a, $b) {
                    return filemtime($a) < filemtime($b);
                });
            }
        }

        return $data;
    }

    /**
     * @param array  $data
     * @param string $filter
     *
     * @return array
     */
    public static function filterData($data, $filter)
    {
        $filterArray = explode(" ", $filter);
        $res = array();

        for ($i = 0; $i < count($data); $i++) {
            $row = $data[$i];

            if (strlen($filter) > 0) {
                $comp = array();
                for ($j = 0; $j < count($filterArray); $j++) {
                    $comp[$j] = false;
                    if (stripos($data[$i], $filterArray[$j]) !== false) {
                        $comp[$j] = true;
                    }
                }
                $comp = array_reduce($comp, function ($a, $b) {
                    return $a && $b;
                }, true);
                if ($comp) {
                    $res[] = $row;
                }
            } else {
                $res[] = $row;
            }
        }

        return $res;
    }

    /**
     * @param string $path
     * @param array  $exclude
     *
     * @return array
     */
    public static function getFolders($path, $exclude = array())
    {
        $elements = scandir($path);
        $folders = array();
        $exc = array_merge($exclude, array(".", "..", "\$RECYCLE.BIN", "System Volume Information"));
        foreach ($elements as $ele) {
            if (!in_array($ele, $exc)) {
                if (is_dir($path.$ele)) {
                    $folders[] = $ele;
                }
            }
        }

        return $folders;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public static function checkUrl($url)
    {
        if (!function_exists('curl_init')) {
            die('Sorry cURL is not installed!');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode < 400);
    }
}
