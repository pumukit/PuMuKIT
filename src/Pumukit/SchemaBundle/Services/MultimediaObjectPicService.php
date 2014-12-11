<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\MultimediaObjectPic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
   TODO:

   [ ] Resize images?
   [x] Configure paths
   [ ] Global configuration paths

 */

class MultimediaObjectPicService
{
    private $dm;
    private $targetPath;
    private $targetUrl;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl)
    {
        $this->dm = $documentManager;
        $this->targetPath = $targetPath;
        $this->targetUrl = $targetUrl;
    }

  /**
   * Get pics from series or multimedia object
   */
  public function getRecomendedPics($series, $page, $limit)
  {
      $offset = ($page - 1) * $limit;
      $total = 0;

      //TODO paginate..
      //TODO Execute RAW mongo query.

      $list = new ArrayCollection();
      foreach ($series->getMultimediaObjects() as $mmobj) {
          foreach ($mmobj->getPics() as $pic) {
              $list->add($pic);
          }
      }

      return array($list, 0);
  }

  /**
   * Set a pic from an url into the multimediaObject
   */
  public function addPicUrl(MultimediaObject $multimediaObject, $picUrl)
  {
      //TODO check URL is valid and a image.
    $pic = new MultimediaObjectPic();
      $pic->setUrl($picUrl);

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

      return $multimediaObject;
  }

  /**
   * Set a pic from an url into the multimediaObject
   */
  public function addPicFile(MultimediaObject $multimediaObject, File $picFile)
  {
      //TODO check file is a image
    //TODO delete double slash "//"
    $path = $picFile->move($this->targetPath."/".$multimediaObject->getId(), $picFile->getClientOriginalName());

      $pic = new MultimediaObjectPic();
      $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

      $multimediaObject->addPic($pic);
      $this->dm->persist($multimediaObject);
      $this->dm->flush();

      return $multimediaObject;
  }
}
