<?php

namespace TinyMediaCenter\API\Model;

/**
 * Class MediaFileInfoModel
 */
class MediaFileInfoModel
{
    /**
     * getId3 result key: File play time.
     */
    const KEY_PLAYTIME_STRING = 'playtime_string';

    /**
     * getId3 result key: video info.
     */
    const KEY_VIDEO = 'video';

    /**
     * getId3 result key: horizontal video resolution.
     */
    const KEY_RESOLUTION_X = 'resolution_x';

    /**
     * getId3 result key: vertical video resolution.
     */
    const KEY_RESOLUTION_Y = 'resolution_y';

    /**
     * getId3 result key: audio info.
     */
    const KEY_AUDIO = 'audio';

    /**
     * getId3 result key: audio channels.
     */
    const KEY_CHANNELS = 'channels';

    /**
     * getId3 result key: 2 channel audio.
     */
    const AUDIO_CHANNELS_2 = '2';

    /**
     * getId3 result key: 5.1 channel audio.
     */
    const AUDIO_CHANNELS_5_1 = '5.1';

    /**
     * @var \getID3
     */
    private $fileInfo;

    /**
     * @var string
     */
    private $duration;

    /**
     * @var string
     */
    private $resolutionX;

    /**
     * @var string
     */
    private $resolutionY;

    /**
     * @var string
     */
    private $sound;

    /**
     * Id3Model constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $getID3 = new \getID3();
        $this->fileInfo = $getID3->analyze($path);
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        if (null === $this->duration) {
            $duration = $this->fileInfo[self::KEY_PLAYTIME_STRING];
            $tmp = substr($this->fileInfo[self::KEY_PLAYTIME_STRING], 0, strrpos($duration, ':'));

            if (strpos($tmp, ':') !== false) {
                $duration = $tmp;
            } else {
                $duration = '0:'.$tmp;
            }

            $this->duration = $duration;
        }

        return $this->duration;
    }

    /**
     * @return string
     */
    public function getResolutionX()
    {
        if (null === $this->resolutionX) {
            $this->resolutionX = $this->fileInfo[self::KEY_VIDEO][self::KEY_RESOLUTION_X];
        }

        return $this->resolutionX;
    }

    /**
     * @return string
     */
    public function getResolutionY()
    {
        if (null === $this->resolutionY) {
            $this->resolutionY = $this->fileInfo[self::KEY_VIDEO][self::KEY_RESOLUTION_Y];
        }

        return $this->resolutionY;
    }

    /**
     * @return string
     */
    public function getResolution()
    {
        return sprintf('%s x %s', $this->getResolutionX(), $this->getResolutionY());
    }

    /**
     * @return string
     */
    public function getSound()
    {
        if (null === $this->sound) {
            $sound = $this->fileInfo[self::KEY_AUDIO][self::KEY_CHANNELS];

            if (self::AUDIO_CHANNELS_2 === $sound) {
                $sound = 'Stereo';
            } elseif (self::AUDIO_CHANNELS_5_1 === $sound) {
                $sound = 'DD 5.1';
            }

            $this->sound = $sound;
        }

        return $this->sound;
    }

    /**
     * @return string
     */
    public function getAsString()
    {
        return sprintf('%s, %s, %s', $this->getDuration(), $this->getResolution(), $this->getSound());
    }
}
