<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DynamicPicExtractorService
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var MultimediaObjectPicService
     */
    private $mmsPicService;

    private $targetPath;
    private $targetUrl;
    private $command;

    private $predefinedTags = [
        'auto',
        'dynamic',
    ];

    /**
     * DynamicPicExtractorService constructor.
     *
     * @param DocumentManager            $documentManager
     * @param MultimediaObjectPicService $mmsPicService
     * @param                            $targetPath
     * @param                            $targetUrl
     * @param                            $command
     */
    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService, $targetPath, $targetUrl, $command)
    {
        $this->documentManager = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing dynamic pic does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->command = $command;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function extract(MultimediaObject $multimediaObject, Track $track)
    {
        if (!file_exists($track->getPath())) {
            throw new \Exception("Path doesn't exists for multimedia object ".$multimediaObject->getId());
        }
        $absCurrentDir = $this->createDir($multimediaObject);
        $fileName = $this->generateFileName($absCurrentDir);
        $vars = [
            '{{input}}' => $track->getPath(),
            '{{output}}' => $absCurrentDir.'/'.$fileName,
        ];
        $commandLine = str_replace(array_keys($vars), array_values($vars), $this->command);
        $this->executeProcess($commandLine);
        $fileUrl = $this->mmsPicService->getTargetUrl($multimediaObject).'/'.$fileName;
        $filePath = $absCurrentDir.'/'.$fileName;

        if (file_exists($filePath)) {
            $multimediaObject = $this->mmsPicService->addPicUrl($multimediaObject, $fileUrl);
            $file = $this->checkFileExists($multimediaObject, $fileUrl);
            $this->completeFileMetadata($multimediaObject, $file, $filePath);
        }

        return true;
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    private function createDir(MultimediaObject $multimediaObject)
    {
        $absCurrentDir = $this->mmsPicService->getTargetPath($multimediaObject);

        $fs = new Filesystem();
        $fs->mkdir($absCurrentDir);

        return $absCurrentDir;
    }

    /**
     * @param $absCurrentDir
     *
     * @return mixed
     */
    private function generateFileName($absCurrentDir)
    {
        $extension = '.webp';
        $fileName = date('ymdGis').$extension;
        while (file_exists($absCurrentDir.'/'.$fileName)) {
            $fileName = date('ymdGis').rand().$extension;
        }

        return $fileName;
    }

    /**
     * @param $commandLine
     */
    private function executeProcess($commandLine)
    {
        $process = new Process($commandLine);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param string           $fileUrl
     *
     * @return null|mixed
     */
    private function checkFileExists(MultimediaObject $multimediaObject, $fileUrl)
    {
        foreach ($multimediaObject->getPics() as $pic) {
            if ($fileUrl == $pic->getUrl()) {
                return $pic;
            }
        }

        return null;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Pic              $file
     * @param string           $filePath
     *
     * @return MultimediaObject
     */
    private function completeFileMetadata(MultimediaObject $multimediaObject, Pic $file, $filePath = '')
    {
        $file->setPath($filePath);
        foreach ($this->predefinedTags as $tag) {
            $file->addTag($tag);
        }

        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        return $multimediaObject;
    }
}
