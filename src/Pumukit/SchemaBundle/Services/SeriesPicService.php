<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\File;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesPic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
   TODO:

   [ ] Resize images?
   [x] Configure paths
   [ ] Global configuration paths

 */

class SeriesPicService
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
   * Set a pic from an url into the series
   */
  public function addPicUrl(Series $series, $picUrl)
  {
      //TODO check URL is valid and a image.
    $pic = new SeriesPic();
      $pic->setUrl($picUrl);

      $series->addPic($pic);
      $this->dm->persist($series);
      $this->dm->flush();

      return $series;
  }

  /**
   * Set a pic from an url into the series
   */
  public function addPicFile(Series $series, File $picFile)
  {
      //TODO check file is a image
    //TODO delete double slash "//"
    $path = $picFile->move($this->targetPath."/".$series->getId(), $picFile->getClientOriginalName());

      $pic = new SeriesPic();
      $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

      $series->addPic($pic);
      $this->dm->persist($series);
      $this->dm->flush();

      return $series;
  }
}
