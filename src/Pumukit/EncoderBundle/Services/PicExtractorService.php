<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;

class PicExtractorService
{
    private $dm;
    private $width;
    private $height;
    private $targetPath;
    private $targetUrl;
    private $command;
    private $mmsPicService;

    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService, $width, $height, $targetPath, $targetUrl, $command = null)
    {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->width = $width;
        $this->height = $height;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath) {
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pic does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->command = $command ?: 'avprobe -ss {{ss}} -y -i "{{input}}" -r 1 -vframes 1 -s {{size}} -f image2 "{{output}}"';
    }

    /**
     * Extract pics on batch.
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     * @param array            $marks
     *
     * @return string $message
     */
    public function extractPicOnBatch(MultimediaObject $multimediaObject, Track $track, array $marks = null)
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
    }

    /**
     * Extract Pic.
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     * @param int              $numframe
     *
     * @return string $message
     */
    public function extractPic(MultimediaObject $multimediaObject, Track $track, $numframe)
    {
        if (!file_exists($track->getPath())) {
            return 'Error in data autocomplete of multimedia object.';
        }

        $num_frames = $track->getNumFrames();

        if ((is_null($numframe) || (0 == $num_frames))) {
            $num = 125 * (count($multimediaObject->getPics())) + 1;
        } elseif ('%' === substr($numframe, -1, 1)) {
            $num = intval($numframe) * $num_frames / 100;
        } else {
            $num = intval($numframe);
        }

        $this->createPic($multimediaObject, $track, $num);

        return 'Captured the FRAME '.$num.' as image.';
    }

    /**
     * Utilizando la libreria ffmpeg_php se genera un Pic que se asocia con el objeto
     * multimedia al que pertenece el archivo.
     *
     * @param MultimediaObject $multimediaObject
     * @param Track            $track
     * @param int              $frame            numero del frame donde se realiza la captura
     *
     * @return PIC o null si mal
     */
    private function createPic(MultimediaObject $multimediaObject, Track $track, $frame = 25)
    {
        $absCurrentDir = $this->mmsPicService->getTargetPath($multimediaObject);

        $fs = new Filesystem();
        $fs->mkdir($absCurrentDir);

        $picFileName = date('ymdGis').'.jpg';
        while (file_exists($absCurrentDir.'/'.$picFileName)) {
            $picFileName = date('ymdGis').rand().'.jpg';
        }

        $aspectTrack = $this->getAspect($track);
        if (0 !== $aspectTrack) {
            $newHeight = intval(1.0 * $this->width / $aspectTrack);
            if ($newHeight <= $this->height) {
                $newWidth = $this->width;
            } else {
                $newHeight = $this->height;
                $newWidth = intval(1.0 * $this->height * $aspectTrack);
            }
        } else {
            $newHeight = $this->height;
            $newWidth = $this->width;
        }

        $vars = [
            '{{ss}}' => $track->getTimeOfAFrame($frame),
            '{{size}}' => $newWidth.'x'.$newHeight,
            '{{input}}' => $track->getPath(),
            '{{output}}' => $absCurrentDir.'/'.$picFileName,
        ];

        $commandLine = str_replace(array_keys($vars), array_values($vars), $this->command);
        $process = new Process($commandLine);
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
            $tags = ['auto', 'frame_'.$frame, 'time_'.$track->getTimeOfAFrame($frame)];
            $multimediaObject = $this->completePicMetadata($multimediaObject, $pic, $picPath, $newWidth, $newHeight, $tags);
        }

        return true;
    }

    /**
     * Get aspect
     * Return aspect ratio. Check is not zero.
     *
     * @param Track $track
     *
     * @return float aspect ratio
     */
    private function getAspect(Track $track)
    {
        if (0 == $track->getHeight()) {
            return 0;
        }

        return 1.0 * $track->getWidth() / $track->getHeight();
    }

    /**
     * Complete pic metadata.
     *
     * Pic service addPicUrl doesn't add the path
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $picUrl
     * @param string           $picPath
     * @param int              $width
     * @param int              $height
     *
     * @return MultimediaObject $multimediaObject
     */
    private function completePicMetadata(MultimediaObject $multimediaObject, Pic $pic, $picPath = '', $width = 0, $height = 0, array $tags = [])
    {
        $pic->setPath($picPath);
        $pic->setWidth($width);
        $pic->setHeight($height);
        foreach ($tags as $tag) {
            $pic->addTag($tag);
        }

        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }

    /**
     * Private method needed because MmsPicService::addPicUrl doesn't return
     * the Pic instance (#9065).
     *
     * @param MultimediaObject $multimediaObject
     * @param string           $picUrl
     *
     * @return Pic|null
     */
    private function getPicByUrl(MultimediaObject $multimediaObject, $picUrl)
    {
        foreach ($multimediaObject->getPics() as $pic) {
            if ($picUrl == $pic->getUrl()) {
                return $pic;
            }
        }

        return null;
    }
}
