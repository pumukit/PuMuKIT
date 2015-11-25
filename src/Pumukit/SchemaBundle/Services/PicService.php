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

    /**
     * @var RequestContext $context
     */
    protected $context;

    public function __construct(RequestContext $context, $defaultSeriesPic='', $defaultVideoPic='', $defaultAudioHDPic='', $defaultAudioSDPic='')
    {
        $this->context = $context;
        $this->defaultSeriesPic = $defaultSeriesPic;
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
                if ($picUrl = $pic->getUrl() && !$pic->getHide() && !$pic->containsTag('banner')) break;
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
            if ("/" == $picUrl[0]) {
              $scheme = $this->context->getScheme();
              $host = $this->context->getHost();
              $port = '';
              if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
                  $port = ':'.$this->context->getHttpPort();
              } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
                  $port = ':'.$this->context->getHttpsPort();
              }

              return $scheme."://".$host.$port.$picUrl;
            }
        }

        return $picUrl;
    }
}