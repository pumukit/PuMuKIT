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
    /** @var DocumentManager */
    private $documentManager;

    /** @var MultimediaObjectPicService */
    private $mmsPicService;
    private $command;
    private $predefinedTags = [
        'auto',
        'dynamic',
    ];

    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService, string $targetPath, string $command)
    {
        $this->documentManager = $documentManager;
        $this->mmsPicService = $mmsPicService;
        if (!realpath($targetPath)) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing dynamic pic does not exist.");
        }
        $this->command = $command;
    }

    public function extract(MultimediaObject $multimediaObject, Track $track): bool
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

    private function createDir(MultimediaObject $multimediaObject): string
    {
        $absCurrentDir = $this->mmsPicService->getTargetPath($multimediaObject);

        $fs = new Filesystem();
        $fs->mkdir($absCurrentDir);

        return $absCurrentDir;
    }

    private function generateFileName(string $absCurrentDir): string
    {
        $extension = '.webp';
        $fileName = date('ymdGis').$extension;
        while (file_exists($absCurrentDir.'/'.$fileName)) {
            $fileName = date('ymdGis').mt_rand().$extension;
        }

        return $fileName;
    }

    private function executeProcess($commandLine): void
    {
        $process = new Process($commandLine);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    private function checkFileExists(MultimediaObject $multimediaObject, string $fileUrl)
    {
        foreach ($multimediaObject->getPics() as $pic) {
            if (($pic instanceof Pic) && $fileUrl === $pic->getUrl()) {
                return $pic;
            }
        }

        return null;
    }

    private function completeFileMetadata(MultimediaObject $multimediaObject, Pic $file, string $filePath = ''): MultimediaObject
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
