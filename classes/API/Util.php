<?php
namespace TinyMediaCenter\API;

/**
 * Class Util
 */
class Util
{

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, Util::globRecursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public static function readJSONFile($file)
    {
        $fileData = file_get_contents($file);
        if (!mb_check_encoding($fileData, 'UTF-8')) {
            $fileData = utf8_encode($fileData);
        }
        $res = json_decode($fileData, true);

        return $res;
    }

    /**
     * @param string $file
     * @param array  $data
     *
     * @return bool
     */
    public static function writeJSONFile($file, $data)
    {
        $json = json_encode($data);
        $pp = Util::prettyPrint($json);
        $res = file_put_contents($file, $pp);

        return ($res !== false);
    }

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
     * @param string $json
     *
     * @return string
     */
    public static function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $prevChar = '';
        $inQuotes = false;
        $endsLineLevel = null;
        $jsonLength = strlen($json);

        for ($i = 0; $i < $jsonLength; $i++) {
            $char = $json[$i];
            $newLineLevel = null;
            $post = "";
            if ($endsLineLevel !== null) {
                $newLineLevel = $endsLineLevel;
                $endsLineLevel = null;
            }
            if ($char === '"' && $prevChar != '\\') {
                $inQuotes = !$inQuotes;
            } else {
                if (!$inQuotes) {
                    switch ($char) {
                        case '}':
                        case ']':
                            $level--;
                            $endsLineLevel = null;
                            $newLineLevel = $level;
                            break;
                        //fall-through
                        case '{':
                        //fall-through
                        case '[':
                            $level++;
                        //fall-through
                        case ',':
                            $endsLineLevel = $level;
                            break;
                        case ':':
                            $post = " ";
                            break;
                        case " ":
                        case "\t":
                        case "\n":
                        case "\r":
                            $char = "";
                            $endsLineLevel = $newLineLevel;
                            $newLineLevel = null;
                            break;
                    }
                }
            }
            if ($newLineLevel !== null) {
                $result .= "\r\n".str_repeat("\t", $newLineLevel);
            }
            $result .= $char.$post;
            $prevChar = $char;
        }

        return $result;
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
     * @param string $sourceImagePath
     * @param string $thumbnailImagePath
     * @param int    $width
     * @param int    $height
     *
     * @return bool
     */
    public static function resizeImage($sourceImagePath, $thumbnailImagePath, $width, $height)
    {
        if (file_exists($thumbnailImagePath)) {
            return true;
        }
        list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = getimagesize($sourceImagePath);
        $sourceGdImage = false;
        switch ($sourceImageType) {
            case IMAGETYPE_GIF:
                $sourceGdImage = imagecreatefromgif($sourceImagePath);
                break;
            case IMAGETYPE_JPEG:
                $sourceGdImage = imagecreatefromjpeg($sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceGdImage = imagecreatefrompng($sourceImagePath);
                break;
        }
        if ($sourceGdImage === false) {
            return false;
        }
        $sourceAspectRatio = $sourceImageWidth / $sourceImageHeight;
        $thumbnailAspectRatio = $width / $height;
        if ($sourceImageWidth <= $width && $sourceImageHeight <= $height) {
            $thumbnailImageWidth = $sourceImageWidth;
            $thumbnailImageHeight = $sourceImageHeight;
        } elseif ($thumbnailAspectRatio > $sourceAspectRatio) {
            $thumbnailImageWidth = (int) ($height * $sourceAspectRatio);
            $thumbnailImageHeight = $height;
        } else {
            $thumbnailImageWidth = $width;
            $thumbnailImageHeight = (int) ($width / $sourceAspectRatio);
        }
        $thumbnailGdImage = imagecreatetruecolor($thumbnailImageWidth, $thumbnailImageHeight);
        imagecopyresampled($thumbnailGdImage, $sourceGdImage, 0, 0, 0, 0, $thumbnailImageWidth, $thumbnailImageHeight, $sourceImageWidth, $sourceImageHeight);
        imagejpeg($thumbnailGdImage, $thumbnailImagePath, 90);
        imagedestroy($sourceGdImage);
        imagedestroy($thumbnailGdImage);

        return true;
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
