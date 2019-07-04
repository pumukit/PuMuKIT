<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Finder\Finder;

class MultimediaObjectPicService
{
    private $dm;
    private $repo;
    private $dispatcher;
    private $targetPath;
    private $targetUrl;
    private $forceDeleteOnDisk;

    public function __construct(DocumentManager $documentManager, PicEventDispatcherService $dispatcher, $targetPath, $targetUrl, $forceDeleteOnDisk = true)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pics does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->repo = $this->dm->getRepository(MultimediaObject::class);
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
    }

    /**
     * Returns the target path for an object.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getTargetPath(MultimediaObject $multimediaObject)
    {
        return $this->targetPath.'/series/'.$multimediaObject->getSeries()->getId().'/video/'.$multimediaObject->getId();
    }

    /**
     * Returns the target url for an object.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getTargetUrl(MultimediaObject $multimediaObject)
    {
        return $this->targetUrl.'/series/'.$multimediaObject->getSeries()->getId().'/video/'.$multimediaObject->getId();
    }

    /**
     * Get pics from series or multimedia object.
     *
     * @param $series
     *
     * @return mixed
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function getRecommendedPics($series)
    {
        return $this->repo->findDistinctUrlPics();
    }

    /**
     * Set a pic from an url into the multimediaObject.
     *
     * @param MultimediaObject $multimediaObject
     * @param                  $picUrl
     * @param bool             $flush
     * @param bool             $isEventPoster
     *
     * @return MultimediaObject
     */
    public function addPicUrl(MultimediaObject $multimediaObject, $picUrl, $flush = true, $isEventPoster = false)
    {
        $pic = new Pic();
        $pic->setUrl($picUrl);
        if ($isEventPoster) {
            $pic = $this->updatePosterTag($multimediaObject, $pic);
        }

        $multimediaObject->addPic($pic);
        $this->dm->persist($multimediaObject);
        if ($flush) {
            $this->dm->flush();
        }

        $this->dispatcher->dispatchCreate($multimediaObject, $pic);

        return $multimediaObject;
    }

    /**
     * Set a pic from an url into the multimediaObject.
     *
     * @param MultimediaObject $multimediaObject
     * @param UploadedFile     $picFile
     * @param bool             $isEventPoster
     *
     * @return MultimediaObject
     *
     * @throws \Exception
     */
    public function addPicFile(MultimediaObject $multimediaObject, UploadedFile $picFile, $isEventPoster = false)
    {
        if (UPLOAD_ERR_OK != $picFile->getError()) {
            throw new \Exception($picFile->getErrorMessage());
        }

        if (!is_file($picFile->getPathname())) {
            throw new FileNotFoundException($picFile->getPathname());
        }

        $path = $picFile->move($this->getTargetPath($multimediaObject), $picFile->getClientOriginalName());

        $pic = new Pic();
        $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
        $pic->setPath($path);

        if ($isEventPoster) {
            $pic = $this->updatePosterTag($multimediaObject, $pic);
        }

        $multimediaObject->addPic($pic);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($multimediaObject, $pic);

        return $multimediaObject;
    }

    /**
     * Set a pic from a memory string.
     *
     * @param MultimediaObject $multimediaObject
     * @param                  $pic
     * @param string           $format
     *
     * @return MultimediaObject
     */
    public function addPicMem(MultimediaObject $multimediaObject, $pic, $format = 'png')
    {
        $absCurrentDir = $this->getTargetPath($multimediaObject);

        $fs = new Filesystem();
        $fs->mkdir($absCurrentDir);

        $mongoId = new \MongoId();

        $fileName = $mongoId.'.'.$format;
        $path = $absCurrentDir.'/'.$fileName;
        while (file_exists($path)) {
            $mongoId = new \MongoId();
            $fileName = $mongoId.'.'.$format;
            $path = $absCurrentDir.'/'.$fileName;
        }

        file_put_contents($path, $pic);

        $pic = new Pic();
        $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
        $pic->setPath($path);

        $multimediaObject->addPic($pic);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $this->dispatcher->dispatchCreate($multimediaObject, $pic);

        return $multimediaObject;
    }

    /**
     * Remove Pic from Multimedia Object.
     *
     * @param MultimediaObject $multimediaObject
     * @param                  $picId
     *
     * @return MultimediaObject
     *
     * @throws \Exception
     */
    public function removePicFromMultimediaObject(MultimediaObject $multimediaObject, $picId)
    {
        $pic = $multimediaObject->getPicById($picId);
        $picPath = $pic->getPath();

        $multimediaObject->removePicById($picId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        if ($this->forceDeleteOnDisk && $picPath) {
            $otherPics = $this->repo->findBy(['pics.path' => $picPath]);
            if (0 == count($otherPics)) {
                $this->deleteFileOnDisk($picPath, $multimediaObject);
            }
        }

        $this->dispatcher->dispatchDelete($multimediaObject, $pic);

        return $multimediaObject;
    }

    /**
     * @param $path
     * @param $multimediaObject
     *
     * @throws \Exception
     */
    private function deleteFileOnDisk($path, $multimediaObject)
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);
        try {
            $deleted = unlink($path);
            if (!$deleted) {
                throw new \Exception("Error deleting file '".$path."' on disk");
            }
            if (0 < strpos($dirname, $multimediaObject->getId())) {
                $finder = new Finder();
                $finder->files()->in($dirname);
                if (0 === $finder->count()) {
                    $dirDeleted = rmdir($dirname);
                    if (!$dirDeleted) {
                        throw new \Exception("Error deleting directory '".$dirname."'on disk");
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Pic              $pic
     *
     * @return Pic
     */
    private function updatePosterTag(MultimediaObject $multimediaObject, Pic $pic)
    {
        foreach ($multimediaObject->getPicsWithTag('poster') as $posterPic) {
            $multimediaObject->removePic($posterPic);
        }
        $pic->addTag('poster');

        return $pic;
    }
}
