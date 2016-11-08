<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
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

    public function __construct(DocumentManager $documentManager, PicEventDispatcherService $dispatcher, $targetPath, $targetUrl, $forceDeleteOnDisk=true)
    {
        $this->dm = $documentManager;
        $this->dispatcher = $dispatcher;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pics does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
    }

    /**
     * Returns the target path for a series
     */
    public function getTargetPath(MultimediaObject $multimediaObject)
    {
        return $this->targetPath.'/'.$multimediaObject->getId();
    }

    /**
     * Returns the target url for a series
     */
    public function getTargetUrl(MultimediaObject $multimediaObject)
    {
        return $this->targetUrl.'/'.$multimediaObject->getId();
    }

  /**
   * Get pics from series or multimedia object
   */
  public function getRecommendedPics($series)
  {
      return $this->repo->findDistinctUrlPics();
  }

  /**
   * Set a pic from an url into the multimediaObject
   */
  public function addPicUrl(MultimediaObject $multimediaObject, $picUrl, $flush = true)
  {
      $pic = new Pic();
      $pic->setUrl($picUrl);

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      if ($flush) {
          $this->dm->flush();
      }

      $this->dispatcher->dispatchCreate($multimediaObject, $pic);

      return $multimediaObject;
  }

  /**
   * Set a pic from an url into the multimediaObject
   */
  public function addPicFile(MultimediaObject $multimediaObject, UploadedFile $picFile)
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

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

      $this->dispatcher->dispatchCreate($multimediaObject, $pic);

      return $multimediaObject;
  }

    /**
     * Remove Pic from Multimedia Object
     */
    public function removePicFromMultimediaObject(MultimediaObject $multimediaObject, $picId)
    {
        $pic = $multimediaObject->getPicById($picId);
        $picPath = $pic->getPath();

        $multimediaObject->removePicById($picId);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        if ($this->forceDeleteOnDisk && $picPath) {
            $otherPics = $this->repo->findBy(array('pics.path' => $picPath));
            if (count($otherPics) == 0) {
                $this->deleteFileOnDisk($picPath, $multimediaObject);
            }
        }

        $this->dispatcher->dispatchDelete($multimediaObject, $pic);

        return $multimediaObject;
    }

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
                    if (!$deleted) {
                        throw new \Exception("Error deleting directory '".$dirname."'on disk");
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
