<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Component\Process\Process;

class DynamicPicExtractorService
{
    public const DEFAULT_WIDTH = 768;
    public const DEFAULT_HEIGHT = 432;

    private $documentManager;
    private $mmsPicService;
    private $command;
    private $logger;
    private $predefinedTags = [
        'auto',
        'dynamic',
    ];

    public function __construct(
        DocumentManager $documentManager,
        MultimediaObjectPicService $mmsPicService,
        LoggerInterface $logger,
        string $targetPath,
        string $command
    ) {
        $this->documentManager = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->logger = $logger;
        if (!realpath($targetPath)) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing dynamic pic does not exist.");
        }
        $this->command = $command;
    }

    public function extract(MultimediaObject $multimediaObject, Track $track): bool
    {
        if (!file_exists($track->storage()->path()->path())) {
            throw new \Exception("Path doesn't exists for multimedia object ".$multimediaObject->getId());
        }

        if (number_format($track->metadata()->width() / $track->metadata()->height(), 2) !== number_format(self::DEFAULT_WIDTH / self::DEFAULT_HEIGHT, 2)) {
            $this->logger->warning('Webp needs 16:9 video '.$multimediaObject->getId());

            return false;
        }

        $absCurrentDir = $this->createDir($multimediaObject);
        $fileName = $this->generateFileName($absCurrentDir);
        $vars = [
            '{{input}}' => $track->storage()->path()->path(),
            '{{output}}' => $absCurrentDir.'/'.$fileName,
            '{{width}}' => self::DEFAULT_WIDTH,
            '{{height}}' => self::DEFAULT_HEIGHT,
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
        FileSystemUtils::createFolder($absCurrentDir);

        return $absCurrentDir;
    }

    private function generateFileName(string $absCurrentDir): string
    {
        $extension = '.webp';
        $fileName = date('ymdGis').$extension;
        while (file_exists($absCurrentDir.'/'.$fileName)) {
            $fileName = date('ymdGis').random_int(0, mt_getrandmax()).$extension;
        }

        return $fileName;
    }

    private function executeProcess($commandLine): void
    {
        if (is_string($commandLine)) {
            $process = Process::fromShellCommandline($commandLine);
        } else {
            $process = new Process($commandLine);
        }
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
