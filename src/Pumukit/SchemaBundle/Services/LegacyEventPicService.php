<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Pic;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LegacyEventPicService
{
    private $dm;
    private $targetPath;
    private $targetUrl;
    private $forceDeleteOnDisk;

    public function __construct(DocumentManager $documentManager, $targetPath, $targetUrl, $forceDeleteOnDisk = true)
    {
        $this->dm = $documentManager;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pics does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->forceDeleteOnDisk = $forceDeleteOnDisk;
    }

    /**
     * Set a pic from an url into the event.
     *
     * @param mixed $picUrl
     */
    public function addPicUrl(Event $event, $picUrl)
    {
        $pic = new Pic();
        $pic->setUrl($picUrl);

        $event->setPic($pic);
        $this->dm->persist($event);
        $this->dm->flush();

        return $event;
    }

    /**
     * Set a pic from an url into the event.
     */
    public function addPicFile(Event $event, UploadedFile $picFile)
    {
        if (UPLOAD_ERR_OK != $picFile->getError()) {
            throw new \Exception($picFile->getErrorMessage());
        }

        if (!is_file($picFile->getPathname())) {
            throw new FileNotFoundException($picFile->getPathname());
        }

        $path = $picFile->move($this->targetPath.'/'.$event->getId(), $picFile->getClientOriginalName());

        $pic = new Pic();
        $pic->setUrl(str_replace($this->targetPath, $this->targetUrl, $path));
        $pic->setPath($path);

        $event->setPic($pic);
        $this->dm->persist($event);
        $this->dm->flush();

        return $event;
    }

    /**
     * Remove Pic from Event.
     */
    public function removePicFromEvent(Event $event)
    {
        $pic = $event->getPic();
        $picPath = $pic->getPath();

        $event->removePic();
        $this->dm->persist($event);
        $this->dm->flush();

        if ($this->forceDeleteOnDisk && $picPath) {
            $this->deleteFileOnDisk($picPath, $event);
        }

        return $event;
    }

    private function deleteFileOnDisk($path, $event)
    {
        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        try {
            $deleted = unlink($path);
            if (!$deleted) {
                throw new \Exception("Error deleting file '".$path."' on disk");
            }
            if (0 < strpos($dirname, $event->getId())) {
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
}
