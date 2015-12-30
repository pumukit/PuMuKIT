<?php

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Services\MultimediaObjectPicService;

class PicExtractorService
{
    private $dm;
    private $width;
    private $height;
    private $targetPath;
    private $targetUrl;
    private $command;

    public function __construct(DocumentManager $documentManager, MultimediaObjectPicService $mmsPicService, $width, $height, $targetPath, $targetUrl, $command=null)
    {
        $this->dm = $documentManager;
        $this->mmsPicService = $mmsPicService;
        $this->width = $width;
        $this->height = $height;
        $this->targetPath = realpath($targetPath);
        if (!$this->targetPath){
            throw new \InvalidArgumentException("The path '".$targetPath."' for storing Pic does not exist.");
        }
        $this->targetUrl = $targetUrl;
        $this->command = $command ?: 'avprobe -ss {{ss}} -y -i "{{input}}" -r 1 -vframes 1 -s {{size}} -f image2 "{{output}}"';
    }

    /**
     * Extract Pic
     *
     * @param MultimediaObject $multimediaObject
     * @param Track $track
     * @param integer $numframe
     * @return string $message
     */
    public function extractPic(MultimediaObject $multimediaObject, Track $track, $numframe)
    {
        if (!file_exists($track->getPath())){
            return "Error in data autocomplete of multimedia object.";
        }

        if (false !== strpos($track->getFramerate(), '/')) {
            $aux = explode('/', $track->getFramerate());
            $num_frames = intval($track->getDuration() * intval($aux[0]) / intval($aux[1]));
        } else {
            $num_frames = intval($track->getFramerate() * $track->getDuration());
        }

        if((is_null($numframe)||($num_frames == 0))){
            $num = 125 * (count($multimediaObject->getPics())) + 1;
        }elseif(substr($numframe, -1, 1) === '%'){
            $num = intval($numframe)* $num_frames /100;
        }else{
            $num = intval($numframe);
        }

        $this->createPic($multimediaObject, $track, $num);

        return "Captured the FRAME ".$num." as image.";
    }

    /**
     * Utilizando la libreria ffmpeg_php se genera un Pic que se asocia con el objeto
     * multimedia al que pertenece el archivo.
     *
     * @param MultimediaObject $multimediaObject
     * @param Track $track
     * @param integer $frame numero del frame donde se realiza la captura.
     * @return PIC o null si mal
     */
    private function createPic(MultimediaObject $multimediaObject, Track $track, $frame = 25)
    {
        $currentDir = 'series/' . $multimediaObject->getSeries()->getId() . '/video/' . $multimediaObject->getId();
        $absCurrentDir = $this->targetPath."/".$currentDir;

        $picFileName = date('ymdGis').'.jpg';
        $aux = null;
        
        $fs = new Filesystem();
        $fs->mkdir($absCurrentDir);

        $aspectTrack = $this->getAspect($track);
        if (0 !== $aspectTrack) {
            $newHeight = intval(1.0 * $this->width / $aspectTrack);
            if ($newHeight <= $this->height) {
                $newWidth = $this->width;
            }else{
                $newHeight = $this->height;
                $newWidth = intval(1.0 * $this->height * $aspectTrack);
            }
        } else {
            $newHeight = $this->height;
            $newWidth = $this->width;
        }

        $vars = array(
            "{{ss}}" => intval($frame/25),
            "{{size}}" => $newWidth . "x" . $newHeight,
            "{{input}}" => $track->getPath(),
            "{{output}}" => $absCurrentDir.'/'.$picFileName
        );

        
        $commandLine = str_replace(array_keys($vars), array_values($vars), $this->command);
        $process = new Process($commandLine);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        //log $process->getOutput()
        $picUrl = $this->targetUrl.'/'.$currentDir.'/'.$picFileName;
        $picPath = $absCurrentDir .'/' . $picFileName;
        if (file_exists($picPath)){
            $multimediaObject = $this->mmsPicService->addPicUrl($multimediaObject, $picUrl);
            $multimediaObject = $this->completePicMetadata($multimediaObject, $picUrl, $picPath, $newWidth, $newHeight);
        }
        
        return true;
    }


    /**
     * Get aspect
     * Return aspect ratio. Check is not zero.
     *
     * @param Track $track
     * @return float aspect ratio
     */
    private function getAspect(Track $track){
      if (0 == $track->getHeight()) return 0;
      return (1.0 * $track->getWidth() / $track->getHeight());
    }

    /**
     * Complete pic metadata
     *
     * Pic service addPicUrl doesn't add the path
     *
     * @param MultimediaObject $multimediaObject
     * @param string $picUrl
     * @param string $picPath
     * @param int    $width
     * @param int    $height
     * @return MultimediaObject $multimediaObject
     */
    private function completePicMetadata(MultimediaObject $multimediaObject, $picUrl='', $picPath='', $width = 0, $height = 0)
    {
        foreach ($multimediaObject->getPics() as $pic) {
            if ($picUrl == $pic->getUrl()) {
                $pic->setPath($picPath);
                $pic->setWidth($width);
                $pic->setHeight($height);
            }
        }
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        return $multimediaObject;
    }
}