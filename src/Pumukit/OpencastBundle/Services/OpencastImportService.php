<?php

namespace Pumukit\OpencastBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TrackService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;

class OpencastImportService
{
    private $opencastClient;
    private $dm;
    private $factoryService;
    private $trackService;
    private $tagService;
    private $mmsService;
    private $opencastService;
    private $inspectionService;
    private $otherLocales;
    private $defaultTagImported;
    private $seriesImportService;
    private $customLanguages;

    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, TrackService $trackService, TagService $tagService, MultimediaObjectService $mmsService, ClientService $opencastClient, OpencastService $opencastService, InspectionServiceInterface $inspectionService, array $otherLocales, $defaultTagImported, SeriesImportService $seriesImportService, array $customLanguages)
    {
        $this->opencastClient = $opencastClient;
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->trackService = $trackService;
        $this->tagService = $tagService;
        $this->mmsService = $mmsService;
        $this->opencastService = $opencastService;
        $this->inspectionService = $inspectionService;
        $this->otherLocales = $otherLocales;
        $this->defaultTagImported = $defaultTagImported;
        $this->seriesImportService = $seriesImportService;
        $this->customLanguages = $customLanguages;
    }

    /**
     * Import recording.
     *
     * Given a media package id
     * create a multimedia object
     * with the media package metadata
     *
     * @param string    $opencastId
     * @param bool      $invert
     * @param User|null $loggedInUser
     */
    public function importRecording($opencastId, $invert = false, User $loggedInUser = null)
    {
        $mediaPackage = $this->opencastClient->getMediaPackage($opencastId);
        $this->importRecordingFromMediaPackage($mediaPackage, $invert, $loggedInUser);
    }

    /**
     * Import recording given a mediaPackage.
     *
     * Given a media package
     * create a multimedia object
     * with the media package metadata
     *
     * @param array     $mediaPackage
     * @param bool      $invert
     * @param User|null $loggedInUser
     */
    public function importRecordingFromMediaPackage($mediaPackage, $invert = false, User $loggedInUser = null)
    {
        $series = $this->seriesImportService->importSeries($mediaPackage, $loggedInUser);

        $onemultimediaobjects = null;
        $multimediaobjectsRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mediaPackageId = $this->getMediaPackageField($mediaPackage, 'id');
        if ($mediaPackageId) {
            $onemultimediaobjects = $multimediaobjectsRepo->findOneBy(array('properties.opencast' => $mediaPackageId));
        }

        if (null === $onemultimediaobjects) {
            $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $loggedInUser);
            $multimediaObject->setSeries($series);

            $title = $this->getMediaPackageField($mediaPackage, 'title');
            if ($title) {
                $multimediaObject->setTitle($title);
            }

            $properties = $this->getMediaPackageField($mediaPackage, 'id');
            if ($properties) {
                $multimediaObject->setProperty('opencast', $properties);
                $multimediaObject->setProperty('opencasturl', $this->opencastClient->getPlayerUrl().'?mode=embed&id='.$properties);
            }
            $multimediaObject->setProperty('opencastinvert', boolval($invert));

            $recDate = $this->getMediaPackageField($mediaPackage, 'start');
            if ($recDate) {
                $multimediaObject->setRecordDate($recDate);
            }

            $language = $this->getMediaPackageLanguage($mediaPackage);
            $multimediaObject->setProperty('opencastlanguage', $language);

            foreach ($this->otherLocales as $locale) {
                $multimediaObject->setTitle($title, $locale);
            }

            $media = $this->getMediaPackageField($mediaPackage, 'media');
            $tracks = $this->getMediaPackageField($media, 'track');
            if (isset($tracks[0])) {
                // NOTE: Multiple tracks
                $limit = count($tracks);
                for ($i = 0; $i < $limit; ++$i) {
                    $track = $this->createTrackFromMediaPackage($mediaPackage, $multimediaObject, $i);
                }
            } else {
                // NOTE: Single track
                $track = $this->createTrackFromMediaPackage($mediaPackage, $multimediaObject);
            }

            $attachments = $this->getMediaPackageField($mediaPackage, 'attachments');
            $attachment = $this->getMediaPackageField($attachments, 'attachment');
            if (isset($attachment[0])) {
                $limit = count($attachment);
                for ($j = 0; $j < $limit; ++$j) {
                    $multimediaObject = $this->createPicFromAttachment($attachment, $multimediaObject, $j);
                }
            } else {
                $multimediaObject = $this->createPicFromAttachment($attachment, $multimediaObject);
            }

            $tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
            $opencastTag = $tagRepo->findOneByCod($this->defaultTagImported);
            if ($opencastTag) {
                $tagService = $this->tagService;
                $tagService->addTagToMultimediaObject($multimediaObject, $opencastTag->getId());
            }

            $multimediaObject = $this->mmsService->updateMultimediaObject($multimediaObject);

            if ($track) {
                $opencastUrls = $this->getOpencastUrls($mediaPackageId);
                $this->opencastService->genAutoSbs($multimediaObject, $opencastUrls);
            }
        }
    }

    public function getOpencastUrls($opencastId = '')
    {
        $opencastUrls = array();
        if (null !== $opencastId) {
            try {
                $archiveMediaPackage = $this->opencastClient->getMediapackageFromArchive($opencastId);
            } catch (\Exception $e) {
                // TODO - Trace error
                return $opencastUrls;
            }
            $media = $this->getMediaPackageField($archiveMediaPackage, 'media');
            $tracks = $this->getMediaPackageField($media, 'track');
            if (isset($tracks[0])) {
                // NOTE: Multiple tracks
                $limit = count($tracks);
                for ($i = 0; $i < $limit; ++$i) {
                    $track = $tracks[$i];
                    $opencastUrls = $this->addOpencastUrl($opencastUrls, $track);
                }
            } else {
                // NOTE: Single track
                $track = $tracks;
                $opencastUrls = $this->addOpencastUrl($opencastUrls, $track);
            }
        }

        return $opencastUrls;
    }

    private function addOpencastUrl($opencastUrls = array(), $track = array())
    {
        $type = $this->getMediaPackageField($track, 'type');
        $url = $this->getMediaPackageField($track, 'url');
        if ($type && $url) {
            $opencastUrls[$type] = $url;
        }

        return $opencastUrls;
    }

    public function getMediaPackageField($mediaFields = array(), $field = '')
    {
        if ($mediaFields && $field) {
            if (isset($mediaFields[$field])) {
                return $mediaFields[$field];
            }
        }

        return null;
    }

    public function createTrackFromMediaPackage($mediaPackage, MultimediaObject $multimediaObject, $index = null)
    {
        $media = $this->getMediaPackageField($mediaPackage, 'media');
        $tracks = $this->getMediaPackageField($media, 'track');
        if ($tracks) {
            if (null === $index) {
                $opencastTrack = $tracks;
            } else {
                $opencastTrack = $tracks[$index];
            }
        } else {
            throw new \Exception(sprintf("No media track info in MP '%s'", $multimediaObject->getProperty('opencast')));
        }

        $track = new Track();

        $language = $this->getMediaPackageLanguage($mediaPackage);
        $track->setLanguage($language);

        $tagsArray = $this->getMediaPackageField($opencastTrack, 'tags');
        $tags = $this->getMediaPackageField($tagsArray, 'tag');
        if (isset($tags[0])) {
            // NOTE: Multiple tags
            $limit = count($tags);
            for ($i = 0; $i < $limit; ++$i) {
                $track = $this->addTagToTrack($tags, $track, $i);
            }
        } else {
            // NOTE: Single tag
            $track = $this->addTagToTrack($tags, $track);
        }

        $url = $this->getMediaPackageField($opencastTrack, 'url');
        if ($url) {
            $track->setUrl($url);
            $track->setPath($this->opencastService->getPath($url));
        }

        $mime = $this->getMediaPackageField($opencastTrack, 'mimetype');
        if ($mime) {
            $track->setMimeType($mime);
        }

        $duration = $this->getMediaPackageField($opencastTrack, 'duration');
        if ($duration) {
            $track->setDuration($duration / 1000);
        }

        $audio = $this->getMediaPackageField($opencastTrack, 'audio');
        $encoder = $this->getMediaPackageField($audio, 'encoder');
        $acodec = $this->getMediaPackageField($encoder, 'type');
        if ($acodec) {
            $track->setAcodec($acodec);
        }

        $video = $this->getMediaPackageField($opencastTrack, 'video');
        $encoder = $this->getMediaPackageField($video, 'encoder');
        $vcodec = $this->getMediaPackageField($encoder, 'type');
        if ($vcodec) {
            $track->setVcodec($vcodec);
        }

        $framerate = $this->getMediaPackageField($video, 'framerate');
        if ($framerate) {
            $track->setFramerate($framerate);
        }

        if (!$track->getVcodec() && $track->getAcodec()) {
            $track->setOnlyAudio(true);
        } else {
            $track->setOnlyAudio(false);
        }

        $track->addTag('opencast');
        $track->addTag('display');

        $type = $this->getMediaPackageField($opencastTrack, 'type');
        if ($type) {
            $track->addTag($opencastTrack['type']);
        }

        if ($track->getPath()) {
            $this->inspectionService->autocompleteTrack($track);
        }

        $multimediaObject->setDuration($track->getDuration());

        $this->trackService->addTrackToMultimediaObject($multimediaObject, $track, false);

        return $track;
    }

    private function createPicFromAttachment($attachment, MultimediaObject $multimediaObject, $index = null)
    {
        if ($attachment) {
            if (null === $index) {
                $itemAttachment = $attachment;
            } else {
                $itemAttachment = $attachment[$index];
            }
            $type = $this->getMediaPackageField($itemAttachment, 'type');
            if ('presenter/search+preview' == $type) {
                $tags = $this->getMediaPackageField($itemAttachment, 'tags');
                $url = $this->getMediaPackageField($itemAttachment, 'url');
                if ($tags || $url) {
                    $pic = new Pic();
                    if ($tags) {
                        foreach ($tags as $tag) {
                            $pic->addTag($tag);
                        }
                    }
                    if ($url) {
                        $pic->setUrl($url);
                    }
                    $multimediaObject->addPic($pic);
                }
            }
        }

        return $multimediaObject;
    }

    private function addTagToTrack($tags, Track $track, $index = null)
    {
        if ($tags) {
            if (null === $index) {
                $tag = $tags;
            } else {
                $tag = $tags[$index];
            }
            if (!$track->containsTag($tag)) {
                $track->addTag($tag);
            }
        }

        return $track;
    }

    private function getMediaPackageLanguage($mediaPackage)
    {
        $language = $this->getMediaPackageField($mediaPackage, 'language');
        if ($language) {
            $parsedLocale = \Locale::parseLocale($language);
            if (!$this->customLanguages || in_array($parsedLocale['language'], $this->customLanguages)) {
                return $parsedLocale['language'];
            }
        }

        return  \Locale::getDefault();
    }
}
