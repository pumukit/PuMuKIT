<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Routing\RequestContext;

class PicService
{
    /**
     * @var RequestContext
     */
    protected $context;
    private $defaultSeriesPic;
    private $defaultVideoPic;
    private $defaultAudioHDPic;
    private $defaultAudioSDPic;
    private $webDir;
    private $defaultPlaylistPic;

    public function __construct(RequestContext $context, $webDir = '', $defaultSeriesPic = '', $defaultPlaylistPic = '', $defaultVideoPic = '', $defaultAudioHDPic = '', $defaultAudioSDPic = '')
    {
        $this->context = $context;
        $this->webDir = $webDir;
        $this->defaultSeriesPic = $defaultSeriesPic;
        $this->defaultPlaylistPic = $defaultPlaylistPic;
        $this->defaultVideoPic = $defaultVideoPic;
        $this->defaultAudioHDPic = $defaultAudioHDPic;
        $this->defaultAudioSDPic = $defaultAudioSDPic;
    }

    /**
     * Get first url pic.
     *
     * Get the first url pic of a document,
     * if none is found, returns the default
     * url pic for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param MultimediaObject|Series $object   Object to get the url (using $object->getPics())
     * @param bool                    $absolute Returns absolute path
     * @param bool                    $hd       Returns pic in HD
     *
     * @return string
     */
    public function getFirstUrlPic($object, $absolute = false, $hd = true)
    {
        $pics = $object->getPics();
        $picUrl = null;
        if (0 === count($pics)) {
            return $this->getDefaultUrlPicForObject($object, $absolute, $hd);
        }
        foreach ($pics as $pic) {
            if (($pic->getUrl()) && !$pic->getHide() && !$pic->containsTag('banner') && !$pic->containsTag('poster')) {
                $picUrl = $pic->getUrl();

                break;
            }
        }

        if (!$picUrl) {
            return $this->getDefaultUrlPicForObject($object, $absolute, $hd);
        }

        if ($absolute) {
            return $this->getAbsoluteUrlPic($picUrl);
        }

        return $picUrl;
    }

    /**
     * Get default url pic.
     *
     * Get the default url pic
     * for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param MultimediaObject|Series $object   Object to get the url (using $object->getPics())
     * @param bool                    $absolute Returns absolute path
     * @param bool                    $hd       Returns pic in HD
     *
     * @return string
     */
    public function getDefaultUrlPicForObject($object, $absolute = false, $hd = true)
    {
        if ($object instanceof Series) {
            if (Series::TYPE_PLAYLIST == $object->getType()) {
                return $this->getDefaultPlaylistUrlPic($absolute);
            }

            return $this->getDefaultSeriesUrlPic($absolute);
        }
        if ($object instanceof MultimediaObject) {
            return $this->getDefaultMultimediaObjectUrlPic($absolute, $object->isOnlyAudio(), $hd);
        }

        return $this->getDefaultMultimediaObjectUrlPic($absolute, false, $hd);
    }

    /**
     * Get default series url pic.
     *
     * Returns the default url pic
     * according to absolute url parameter
     *
     * @param bool $absolute Returns absolute path
     *
     * @return string
     */
    public function getDefaultSeriesUrlPic($absolute = false)
    {
        if ($absolute) {
            return $this->getAbsoluteUrlPic($this->defaultSeriesPic);
        }

        return $this->defaultSeriesPic;
    }

    /**
     * Get default playlist url pic.
     *
     * Returns the default url pic
     * according to absolute url parameter
     *
     * @param bool $absolute Returns absolute path
     *
     * @return string
     */
    public function getDefaultPlaylistUrlPic($absolute = false)
    {
        if ($absolute) {
            return $this->getAbsoluteUrlPic($this->defaultPlaylistPic);
        }

        return $this->defaultPlaylistPic;
    }

    /**
     * Get default multimedia object url pic.
     *
     * Returns the default url pic
     * according to absolute url parameter
     * and hd in case of audio
     *
     * @param bool $audio    Video is only audio
     * @param bool $hd       Returns pic in HD
     * @param bool $absolute Returns absolute path
     *
     * @return string
     */
    public function getDefaultMultimediaObjectUrlPic($absolute = false, $audio = false, $hd = true)
    {
        if ($audio) {
            if ($hd) {
                $defaultPic = $this->defaultAudioHDPic;
            } else {
                $defaultPic = $this->defaultAudioSDPic;
            }
        } else {
            $defaultPic = $this->defaultVideoPic;
        }

        if ($absolute) {
            return $this->getAbsoluteUrlPic($defaultPic);
        }

        return $defaultPic;
    }

    /**
     * Get first path pic.
     *
     * Get the first path pic of a document,
     * if none is found, returns the default
     * path pic for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param MultimediaObject|Series $object Object to get the path (using $object->getPics())
     * @param bool                    $hd     Returns pic in HD
     *
     * @return string
     */
    public function getFirstPathPic($object, $hd = true)
    {
        $pics = $object->getPics();
        $picPath = null;
        if (0 === count($pics)) {
            return $this->getDefaultPathPicForObject($object, $hd);
        }
        foreach ($pics as $pic) {
            if (($picPath = $pic->getPath()) && !$pic->getHide() && !$pic->containsTag('banner')) {
                break;
            }
        }

        if (!$picPath) {
            return $this->getDefaultPathPicForObject($object, $hd);
        }

        return $picPath;
    }

    /**
     * Get default path pic.
     *
     * Get the default path pic
     * for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param MultimediaObject|Series $object Object to get the path (using $object->getPics())
     * @param bool                    $hd     Returns pic in HD
     *
     * @return string
     */
    public function getDefaultPathPicForObject($object, $hd = true)
    {
        if ($object instanceof Series) {
            return $this->getDefaultSeriesPathPic();
        }
        if ($object instanceof MultimediaObject) {
            return $this->getDefaultMultimediaObjectPathPic($object->isOnlyAudio(), $hd);
        }

        return $this->getDefaultMultimediaObjectPathPic(false, $hd);
    }

    /**
     * Get default series path pic.
     *
     * @returns string
     */
    public function getDefaultSeriesPathPic()
    {
        return $this->getAbsolutePathPic($this->defaultSeriesPic);
    }

    /**
     * Get default multimedia object path pic.
     *
     * Returns the default path pic
     * according to hd parameter and in case of audio
     *
     * @param bool $audio Video is only audio
     * @param bool $hd    Returns pic in HD
     *
     * @return string
     */
    public function getDefaultMultimediaObjectPathPic($audio = false, $hd = true)
    {
        if ($audio) {
            if ($hd) {
                $defaultPic = $this->defaultAudioHDPic;
            } else {
                $defaultPic = $this->defaultAudioSDPic;
            }
        } else {
            $defaultPic = $this->defaultVideoPic;
        }

        return $this->getAbsolutePathPic($defaultPic);
    }

    /**
     * @param      $object
     * @param bool $absolute
     * @param bool $hd
     *
     * @return null|string
     */
    public function getPosterUrl($object, $absolute = false, $hd = true)
    {
        $pics = $object->getPics();
        $picUrl = null;
        if (0 === count($pics)) {
            return $picUrl;
        }

        foreach ($pics as $pic) {
            if ($pic->getUrl() && $pic->containsTag('poster')) {
                $picUrl = $pic->getUrl();

                break;
            }
        }

        if ($absolute) {
            return $this->getAbsoluteUrlPic($picUrl);
        }

        return $picUrl;
    }

    /**
     * Get absolute path of a given pic url.
     *
     * @param string $picUrl
     *
     * @return string
     */
    protected function getAbsoluteUrlPic($picUrl = '')
    {
        if ($picUrl) {
            if ('/' == $picUrl[0]) {
                $scheme = $this->context->getScheme();
                $host = $this->context->getHost();
                $port = '';
                if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
                    $port = ':'.$this->context->getHttpPort();
                } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
                    $port = ':'.$this->context->getHttpsPort();
                }

                return $scheme.'://'.$host.$port.$picUrl;
            }
        }

        return $picUrl;
    }

    /**
     * Get absolute path of a given pic path.
     *
     * @param string $picPath
     *
     * @return string
     */
    private function getAbsolutePathPic($picPath = '')
    {
        if ($picPath) {
            if ('/' == $picPath[0]) {
                return $this->webDir.$picPath;
            }

            return $this->webDir.'/'.$picPath;
        }

        return $picPath;
    }
}
