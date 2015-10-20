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
    private $targetPath;
    private $targetUrl;
    private $forceDeleteOnDisk;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl, $forceDeleteOnDisk=true)
    {
        $this->dm = $documentManager;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath){
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pics does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
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
  public function addPicUrl(MultimediaObject $multimediaObject, $picUrl)
  {
      $pic = new Pic();
      $pic->setUrl($picUrl);

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

      return $multimediaObject;
  }

  /**
   * Set a pic from an url into the multimediaObject
   */
  public function addPicFile(MultimediaObject $multimediaObject, UploadedFile $picFile)
  {
      if(UPLOAD_ERR_OK != $picFile->getError()) {
          throw new \Exception($picFile->getErrorMessage());
      }

      if (!is_file($picFile->getPathname())) {
          throw new FileNotFoundException($picFile->getPathname());
      }

      $path = $picFile->move($this->targetPath."/".$multimediaObject->getId(), $picFile->getClientOriginalName());

      $pic = new Pic();
      $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
      $pic->setPath($path);

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

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
          $this->deleteFileOnDisk($picPath, $multimediaObject);
        }

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
