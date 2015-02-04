<?php

namespace Pumukit\SchemaBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Pic;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;

class SeriesPicService
{
    private $dm;
    private $repoMmobj;
    private $targetPath;
    private $targetUrl;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl)
    {
        $this->dm = $documentManager;
        $this->targetPath = $targetPath;
        $this->targetUrl = $targetUrl;
        $this->repoMmobj = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

  /**
   * Get pics from series or multimedia object
   */
  public function getRecommendedPics($series)
  {
      return $this->repoMmobj->findDistinctUrlPicsInSeries($series);
  }

  /**
   * Set a pic from an url into the series
   */
  public function addPicUrl(Series $series, $picUrl)
  {
      $pic = new Pic();
      $pic->setUrl($picUrl);

      $series->addPic($pic);
      $this->dm->persist($series);
      $this->dm->flush();

      return $series;
  }

  /**
   * Set a pic from an url into the series
   */
  public function addPicFile(Series $series, UploadedFile $picFile)
  {
      $path = $picFile->move($this->targetPath."/".$series->getId(), $picFile->getClientOriginalName());

      $pic = new Pic();
      $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));

      $series->addPic($pic);
      $this->dm->persist($series);
      $this->dm->flush();

      return $series;
  }
}
