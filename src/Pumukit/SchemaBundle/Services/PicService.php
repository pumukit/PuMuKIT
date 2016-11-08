<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\Routing\RequestContext;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class PicService
{
    private $defaultSeriesPic;
    private $defaultVideoPic;
    private $defaultAudioHDPic;
    private $defaultAudioSDPic;
    private $webDir;

    /**
     * @var RequestContext $context
     */
    protected $context;

    public function __construct(RequestContext $context, $webDir='', $defaultSeriesPic='', $defaultPlaylistPic='', $defaultVideoPic='', $defaultAudioHDPic='', $defaultAudioSDPic='')
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
     * Get first url pic
     *
     * Get the first url pic of a document,
     * if none is found, returns the default
     * url pic for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param Series|MultimediaObject $object   Object to get the url (using $object->getPics())
     * @param boolean                 $absolute Returns absolute path
     * @param boolean                 $hd       Returns pic in HD
     *
     * @return string
     */
    public function getFirstUrlPic($object, $absolute=false, $hd=true)
    {
        $pics = $object->getPics();
        if (0 === count($pics)) {
            return $this->getDefaultUrlPicForObject($object, $absolute, $hd);
        } else {
            foreach ($pics as $pic) {
                if (($picUrl = $pic->getUrl()) && !$pic->getHide() && !$pic->containsTag('banner')) {
                    break;
                }
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
     * Get default url pic
     *
     * Get the default url pic
     * for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param Series|MultimediaObject $object   Object to get the url (using $object->getPics())
     * @param boolean                 $absolute Returns absolute path
     * @param boolean                 $hd       Returns pic in HD
     *
     * @return string
     */
    public function getDefaultUrlPicForObject($object, $absolute=false, $hd=true)
    {
        if ($object instanceof Series) {
            if ($object->getType() == Series::TYPE_PLAYLIST) {
                return $this->getDefaultPlaylistUrlPic($absolute);
            }
            return $this->getDefaultSeriesUrlPic($absolute);
        } elseif ($object instanceof MultimediaObject) {
            return $this->getDefaultMultimediaObjectUrlPic($absolute, $object->isOnlyAudio(), $hd);
        }

        return $this->getDefaultMultimediaObjectUrlPic($absolute, false, $hd);
    }

    /**
     * Get default series url pic
     *
     * Returns the default url pic
     * according to absolute url parameter
     *
     * @param boolean $absolute Returns absolute path
     * @returns string
     */
    public function getDefaultSeriesUrlPic($absolute=false)
    {
        if ($absolute) {
            return $this->getAbsoluteUrlPic($this->defaultSeriesPic);
        }
        return $this->defaultSeriesPic;
    }

    /**
     * Get default playlist url pic
     *
     * Returns the default url pic
     * according to absolute url parameter
     *
     * @param boolean $absolute Returns absolute path
     * @returns string
     */
    public function getDefaultPlaylistUrlPic($absolute=false)
    {
        if ($absolute) {
            return $this->getAbsoluteUrlPic($this->defaultPlaylistPic);
        }
        return $this->defaultPlaylistPic;
    }

    /**
     * Get default multimedia object url pic
     *
     * Returns the default url pic
     * according to absolute url parameter
     * and hd in case of audio
     *
     * @param boolean $audio    Video is only audio
     * @param boolean $hd       Returns pic in HD
     * @param boolean $absolute Returns absolute path
     * @returns string
     */
    public function getDefaultMultimediaObjectUrlPic($absolute=false, $audio=false, $hd=true)
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
     * Get absolute path of a given pic url
     *
     * @param string $picUrl
     * @return string
     */
    private function getAbsoluteUrlPic($picUrl='')
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
     * Get first path pic
     *
     * Get the first path pic of a document,
     * if none is found, returns the default
     * path pic for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param Series|MultimediaObject $object   Object to get the path (using $object->getPics())
     * @param boolean                 $hd       Returns pic in HD
     *
     * @return string
     */
    public function getFirstPathPic($object, $hd=true)
    {
        $pics = $object->getPics();
        if (0 === count($pics)) {
            return $this->getDefaultPathPicForObject($object, $hd);
        } else {
            foreach ($pics as $pic) {
                if (($picPath = $pic->getPath()) && !$pic->getHide() && !$pic->containsTag('banner')) {
                    break;
                }
            }
        }

        if (!$picPath) {
            return $this->getDefaultPathPicForObject($object, $hd);
        }

        return $picPath;
    }

    /**
     * Get default path pic
     *
     * Get the default path pic
     * for a given resource checking if
     * it is Series, MultimediaObject of type
     * video or audio
     *
     * @param Series|MultimediaObject $object   Object to get the path (using $object->getPics())
     * @param boolean                 $hd       Returns pic in HD
     *
     * @return string
     */
    public function getDefaultPathPicForObject($object, $hd=true)
    {
        if ($object instanceof Series) {
            return $this->getDefaultSeriesPathPic();
        } elseif ($object instanceof MultimediaObject) {
            return $this->getDefaultMultimediaObjectPathPic($object->isOnlyAudio(), $hd);
        }

        return $this->getDefaultMultimediaObjectPathPic(false, $hd);
    }

    /**
     * Get default series path pic
     *
     * @returns string
     */
    public function getDefaultSeriesPathPic()
    {
        return $this->getAbsolutePathPic($this->defaultSeriesPic);
    }

    /**
     * Get default multimedia object path pic
     *
     * Returns the default path pic
     * according to hd parameter and in case of audio
     *
     * @param boolean $audio    Video is only audio
     * @param boolean $hd       Returns pic in HD
     * @returns string
     */
    public function getDefaultMultimediaObjectPathPic($audio=false, $hd=true)
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
     * Get absolute path of a given pic path
     *
     * @param string $picPath
     * @return string
     */
    private function getAbsolutePathPic($picPath='')
    {
        if ($picPath) {
            if ('/' == $picPath[0]) {
                return $this->webDir.$picPath;
            } else {
                return $this->webDir.'/'.$picPath;
            }
        }

        return $picPath;
    }
}
