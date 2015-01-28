<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Doctrine\ODM\MongoDB\DocumentManager;

class TrackService
{
    private $dm;
    private $targetPath;
    private $targetUrl;
    private $jobService;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl)
    {
        $this->dm = $documentManager;
        $this->targetPath = $targetPath;
        $this->targetUrl = $targetUrl;
        //TODO inject service $this->jobService = $jobService;
    }

    /**
     * Add Track to Multimedia Object
     */
    public function addTrackToMultimediaObject(MultimediaObject $multimediaObject, File $trackFile, $formData)
    {
        // TODO - Move file to temp dir

        // TODO - Call JobService to encode the track
        $data = $this->getArrayData($formData);
        //$this->jobService->addJob($trackFile, $data['profile'], $data['priority'], $multimediaObject, $data['language'], $data['description']);

        /*
        // TODO - redo
        $track = new Track();
        $track = $this->saveFormData($track, $formData);

        $path = $trackFile->move($this->targetPath."/".$multimediaObject->getId(), $trackFile->getClientOriginalName());

        $track->setPath($path);
        $track->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

        $multimediaObject->addTrack($track);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        */
        return $multimediaObject;
    }

    /**
     * Update Track in Multimedia Object
     */
    public function updateTrackInMultimediaObject(MultimediaObject $multimediaObject)
    {
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Remove Track from Multimedia Object
     */
    public function removeTrackFromMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $multimediaObject->removeTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Up Track in Multimedia Object
     */
    public function upTrackInMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $multimediaObject->upTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Down Track in Multimedia Object
     */
    public function downTrackInMultimediaObject(MultimediaObject $multimediaObject, $trackId)
    {
        $multimediaObject->downTrackById($trackId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Save form data of Track
     *
     * @return Track $track
     */
    private function saveFormData(Track $track, $formData)
    {
        if (array_key_exists('i18n_description', $formData)) {
            $track->setI18nDescription($formData['i18n_description']);
        }
        /*
        if (array_key_exists('hide', $formData)){
            $track->setHide($formData['hide']);
        }
        if (array_key_exists('mime_type', $formData)){
            $track->setMimeType($formData['mime_type']);
        }
        */

        return $track;
    }

    /**
     * Get data in array or default values
     */
    private function getFormData($formData)
    {
        $data = array(
                      'profile' => array('name' => null),
                      'priority' => 2,
                      'language' => null,
                      'description' => array(),
                      );

        if (array_key_exists('profile', $formData)) {
            $data['profile'] = $formData['profile'];
        }
        if (array_key_exists('priority', $formData)) {
            $data['priority'] = $formData['priority'];
        }
        if (array_key_exists('language', $formData)) {
            $data['language'] = $formData['language'];
        }
        if (array_key_exists('i18n_description', $formData)) {
            $data['description'] = $formData['i18n_description'];
        }

        return $data;
    }
}
