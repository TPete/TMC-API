<?php

namespace TinyMediaCenter\API\Service;

/**
 * Class AbstractController
 */
abstract class AbstractCategoryService
{
    /**
     * Get categories.
     */
    abstract public function getCategories();

    /**
     * Get categories.
     */
    abstract public function getCategoryNames();

    /**
     * Update data.
     */
    abstract public function updateData();

    /**
     * @param string $path
     * @param array  $exclude
     *
     * @return array
     */
    protected function getFolders($path, $exclude = [])
    {
        $elements = scandir($path);
        $folders = [];
        $exc = array_merge($exclude, ['.', '..', '$RECYCLE.BIN', 'System Volume Information']);

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
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    protected function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->globRecursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    /**
     * @param string $sourceImagePath
     * @param string $thumbnailImagePath
     * @param int    $width
     * @param int    $height
     *
     * @return bool
     */
    protected function resizeImage($sourceImagePath, $thumbnailImagePath, $width, $height)
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
        imagecopyresampled(
            $thumbnailGdImage,
            $sourceGdImage,
            0,
            0,
            0,
            0,
            $thumbnailImageWidth,
            $thumbnailImageHeight,
            $sourceImageWidth,
            $sourceImageHeight
        );
        imagejpeg($thumbnailGdImage, $thumbnailImagePath, 90);
        imagedestroy($sourceGdImage);
        imagedestroy($thumbnailGdImage);

        return true;
    }
}
