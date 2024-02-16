<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Utils\FileSystemUtils;
use Pumukit\CoreBundle\Utils\FinderUtils;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MediaType\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;
use Symfony\Component\Process\Process;

class PicExtractorService
{
    private DocumentManager $dm;
    private MultimediaObjectPicService $mmsPicService;
    private int $width;
    private int $height;
    private string $command;

    public function __construct(
        DocumentManager $documentManager,
        MultimediaObjectPicService $mmsPicService,
        int $width,
        int $height,
        string $command = null
    ) {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->width = $width;
        $this->height = $height;
        $this->command = $command ?: 'avprobe -ss {{ss}} -y -i "{{input}}" -r 1 -vframes 1 -s {{size}} -f image2 "{{output}}"';
    }

    public function extractPicOnBatch(MultimediaObject $multimediaObject, Track $track, array $marks = null): bool
    {
        if ($multimediaObject->getProperty('imagesonbatch')) {
            return false;
        }

        $multimediaObject->setProperty('imagesonbatch', true);

        if (!$marks) {
            $marks = ['0%', '10%', '20%', '30%', '40%', '50%', '60%', '70%', '80%', '90%'];
        }
        foreach ($marks as $mark) {
            $this->extractPic($multimediaObject, $track, $mark);
        }

        return true;
    }

    public function extractPic(MultimediaObject $multimediaObject, MediaInterface $media, string $numFrame = null): bool
    {
        if (!FinderUtils::isValidFile($media->storage()->path()->path())) {
            return false;
        }

        $num = $this->getNumFrames($media, $multimediaObject, $numFrame);

        $this->createPic($multimediaObject, $media, (int) $num);

        return true;
    }

    public function getNumFrames(MediaInterface $media, MultimediaObject $multimediaObject, string $numFrame): float|int
    {
        $num_frames = $media->metadata()->numFrames();

        if (!$numFrame || (0 == $num_frames)) {
            return 125 * (is_countable($multimediaObject->getPics()) ? count($multimediaObject->getPics()) : 0) + 1;
        }

        if (str_ends_with($numFrame, '%')) {
            return (int) $numFrame * $num_frames / 100;
        }

        return (int) $numFrame;
    }

    private function createPic(MultimediaObject $multimediaObject, MediaInterface $media, int $frame = 25): void
    {
        $absCurrentDir = $this->mmsPicService->getTargetPath($multimediaObject);

        FileSystemUtils::createFolder($absCurrentDir);

        $picFileName = date('ymdGis').'.jpg';
        while (file_exists($absCurrentDir.'/'.$picFileName)) {
            $picFileName = date('ymdGis').random_int(0, mt_getrandmax()).'.jpg';
        }

        $aspectTrack = $this->getAspect($media);
        if (0 !== $aspectTrack) {
            $newHeight = (int) (1.0 * $this->width / $aspectTrack);
            if ($newHeight <= $this->height) {
                $newWidth = $this->width;
            } else {
                $newHeight = $this->height;
                $newWidth = (int) (1.0 * $this->height * $aspectTrack);
            }
        } else {
            $newHeight = $this->height;
            $newWidth = $this->width;
        }

        $vars = [
            '{{ss}}' => $media->metadata()->timeOfaFrame($frame),
            '{{size}}' => $newWidth.'x'.$newHeight,
            '{{input}}' => $media->storage()->path()->path(),
            '{{output}}' => $absCurrentDir.'/'.$picFileName,
        ];

        $commandLine = str_replace(array_keys($vars), array_values($vars), $this->command);
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

        $picUrl = $this->mmsPicService->getTargetUrl($multimediaObject).'/'.$picFileName;
        $picPath = $absCurrentDir.'/'.$picFileName;
        if (file_exists($picPath)) {
            $multimediaObject = $this->mmsPicService->addPicUrl($multimediaObject, $picUrl);
            $pic = $this->getPicByUrl($multimediaObject, $picUrl);
            $tags = ['auto', 'frame_'.$frame, 'time_'.$media->metadata()->timeOfAFrame($frame)];
            $this->completePicMetadata($multimediaObject, $pic, $picPath, $newWidth, $newHeight, $tags);
        }
    }

    /**
     * Return aspect ratio. Check is not zero.
     */
    private function getAspect(MediaInterface $media): float|int
    {
        if (0 == $media->metadata()->height()) {
            return 0;
        }

        return 1.0 * $media->metadata()->width() / $media->metadata()->height();
    }

    /**
     * Pic service addPicUrl doesn't add the path.
     */
    private function completePicMetadata(MultimediaObject $multimediaObject, Pic $pic, string $picPath = '', int $width = 0, int $height = 0, array $tags = []): void
    {
        $pic->setPath($picPath);
        $pic->setWidth($width);
        $pic->setHeight($height);
        foreach ($tags as $tag) {
            $pic->addTag($tag);
        }

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
    }

    /**
     * Private method needed because MmsPicService::addPicUrl doesn't return the Pic instance (#9065).
     */
    private function getPicByUrl(MultimediaObject $multimediaObject, string $picUrl)
    {
        foreach ($multimediaObject->getPics() as $pic) {
            if ($picUrl == $pic->getUrl()) {
                return $pic;
            }
        }

        return null;
    }
}
