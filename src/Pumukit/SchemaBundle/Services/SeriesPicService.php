<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SeriesPicService
{
    private $dm;
    private $repoMmobj;
    private $seriesDispatcher;
    private $locales;
    private $targetPath;
    private $targetUrl;
    private $forceDeleteOnDisk;
    private $defaultBanner = '<a href="#"><img  style="width:100%" src="___banner_url___" border="0"/></a>';

    public function __construct(DocumentManager $documentManager, SeriesEventDispatcherService $seriesDispatcher, array $locales, $targetPath, $targetUrl, $forceDeleteOnDisk = true)
    {
        $this->dm = $documentManager;
        $this->seriesDispatcher = $seriesDispatcher;
        $this->locales = $locales;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pics does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->repoMmobj = $this->dm->getRepository(MultimediaObject::class);
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
    }

    /**
     * Returns the target path for a series.
     */
    public function getTargetPath(Series $series)
    {
        return $this->targetPath.'/series/'.$series->getId();
    }

    /**
     * Returns the target url for a series.
     */
    public function getTargetUrl(Series $series)
    {
        return $this->targetUrl.'/series/'.$series->getId();
    }

    /**
     * Get pics from series or multimedia object.
     *
     * @param mixed $series
     */
    public function getRecommendedPics($series)
    {
        return $this->repoMmobj->findDistinctUrlPicsInSeries($series);
    }

    /**
     * Set a pic from an url into the series.
     *
     * @param mixed $picUrl
     * @param mixed $isBanner
     * @param mixed $bannerTargetUrl
     */
    public function addPicUrl(Series $series, $picUrl, $isBanner = false, $bannerTargetUrl = '')
    {
        $pic = new Pic();
        $pic->setUrl($picUrl);
        if ($isBanner) {
            $pic->setHide(true);
            $pic->addTag('banner');
            $series = $this->addBanner($series, $pic->getUrl(), $bannerTargetUrl);
        }

        $series->addPic($pic);
        $this->dm->persist($series);
        $this->dm->flush();
        $this->seriesDispatcher->dispatchUpdate($series);

        return $series;
    }

    /**
     * Set a pic from an url into the series.
     *
     * @param mixed $isBanner
     * @param mixed $bannerTargetUrl
     */
    public function addPicFile(Series $series, UploadedFile $picFile, $isBanner = false, $bannerTargetUrl = '')
    {
        if (UPLOAD_ERR_OK != $picFile->getError()) {
            throw new \Exception($picFile->getErrorMessage());
        }

        if (!is_file($picFile->getPathname())) {
            throw new FileNotFoundException($picFile->getPathname());
        }

        $path = $picFile->move($this->getTargetPath($series), $picFile->getClientOriginalName());

        $pic = new Pic();
        $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
        $pic->setPath($path);
        if ($isBanner) {
            $pic->setHide(true);
            $pic->addTag('banner');
            $series = $this->addBanner($series, $pic->getUrl(), $bannerTargetUrl);
        }

        $series->addPic($pic);
        $this->dm->persist($series);
        $this->dm->flush();
        $this->seriesDispatcher->dispatchUpdate($series);

        return $series;
    }

    /**
     * Remove Pic from Series.
     *
     * @param mixed $picId
     */
    public function removePicFromSeries(Series $series, $picId)
    {
        $pic = $series->getPicById($picId);
        $picPath = $pic->getPath();
        $picUrl = $pic->getUrl();
        if (in_array('banner', $pic->getTags())) {
            foreach ($this->locales as $locale) {
                if (0 < strpos($series->getHeader($locale), $picUrl)) {
                    $series->setHeader('', $locale);
                }
            }
        }

        $series->removePicById($picId);
        $this->dm->persist($series);
        $this->dm->flush();
        $this->seriesDispatcher->dispatchUpdate($series);

        if ($this->forceDeleteOnDisk && $picPath) {
            $this->deleteFileOnDisk($picPath, $series);
        }

        return $series;
    }

    private function deleteFileOnDisk($path, $series)
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        try {
            $deleted = unlink($path);
            if (!$deleted) {
                throw new \Exception("Error deleting file '".$path."' on disk");
            }
            if (0 < strpos($dirname, $series->getId())) {
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

    private function addBanner(Series $series, $picUrl = '', $bannerTargetUrl = '')
    {
        if ($picUrl) {
            $banner = str_replace('___banner_url___', $picUrl, $this->defaultBanner);
            if ($bannerTargetUrl) {
                $banner = str_replace('#', $bannerTargetUrl, $banner);
            }
            foreach ($this->locales as $locale) {
                $series->setHeader($banner, $locale);
            }
        }

        return $series;
    }
}
