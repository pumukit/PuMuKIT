<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;


class MultimediaObjectPicService
{
    private $dm;
    private $repo;
    private $targetPath;
    private $targetUrl;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl)
    {
        $this->dm = $documentManager;
        $this->targetPath = $targetPath;
        $this->targetUrl = $targetUrl;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
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
      $path = $picFile->move($this->targetPath."/".$multimediaObject->getId(), $picFile->getClientOriginalName());

      $pic = new Pic();
      $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

      return $multimediaObject;
  }
}
