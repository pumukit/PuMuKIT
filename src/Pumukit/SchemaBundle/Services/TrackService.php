<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class TrackService
{
    private $dm;
    private $tmpPath;
    private $jobService;
    private $profileService;

    public function __construct(DocumentManager $documentManager, JobService $jobService, ProfileService $profileService, $tmpPath=null)
    {
        $this->dm = $documentManager;
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->tmpPath = $tmpPath ? $tmpPath : sys_get_temp_dir();
    }

    /**
     * Create track from file with job service
     * 
     * @param MultimediaObject $multimediaObject
     * @param File $file
     * @param array $formData
     * @return MultimediaObject
     */
    public function createTrackFromFile(MultimediaObject $multimediaObject, File $trackFile, $formData)
    {
        $data = $this->getArrayData($formData);

        if (null === $this->profileService->getProfile($data['profile'])){
            throw new \Exception("Can't find given profile with name ".$data['profile']);
        }

        if (!is_file($trackFile->getPathname())) {
            throw new FileNotFoundException($trackFile->getPathname());
        }

        $pathFile = $trackFile->move($this->tmpPath."/".$multimediaObject->getId(), $trackFile->getBasename());

        $this->jobService->addJob($pathFile, $data['profile'], $data['priority'], $multimediaObject, $data['language'], $data['description']);

        return $multimediaObject;
    }

    /**
     * Create track from url with job service
     * 
     * @param MultimediaObject $multimediaObject
     * @param string $trackUrl
     * @param array $formData
     * @return MultimediaObject
     */
    public function createTrackFromUrl(MultimediaObject $multimediaObject, $trackUrl, $formData)
    {
        $data = $this->getArrayData($formData);

        if (null === $this->profileService->getProfile($data['profile'])){
            throw new \Exception("Can't find given profile with name ".$data['profile']);
        }

        if (!is_file($trackUrl)) {
            throw new FileNotFoundException($trackUrl);
        }

        $this->jobService->addJob($trackUrl, $data['profile'], $data['priority'], $multimediaObject, $data['language'], $data['description']);

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
     * Get temp directories
     */
    public function getTempDirs()
    {
        return array($this->tmpPath);
    }

    /**
     * Get data in array or default values
     */
    private function getArrayData($formData)
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
