<?php

namespace Pumukit\OpencastBundle\Services;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ODM\MongoDB\DocumentManager;

use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\OpencastBundle\Services\OpencastService;
use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;

class OpencastImportService
{
    private $opencastClient;
    private $dm;
    private $factoryService;
    private $tagService;
    private $opencastService;
    private $inspectionService;
    private $otherLocales;
    
    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, TagService $tagService, ClientService $opencastClient, OpencastService $opencastService, InspectionServiceInterface $inspectionService, array $otherLocales = array()) {
        $this->opencastClient = $opencastClient;
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->opencastService = $opencastService;
        $this->inspectionService = $inspectionService;
        $this->otherLocales = $otherLocales;
    }


    public function importRecording($opencastId)
    {
        $mediaPackage = $this->opencastClient->getMediaPackage($opencastId);
        $seriesRepo = $this->dm->getRepository('PumukitSchemaBundle:Series');

        if(isset($mediaPackage["series"])){
            $series = $seriesRepo->findOneBy(array("properties.opencast" => $mediaPackage["series"]));
        }else{
            $series = $seriesRepo->findOneBy(array("properties.opencast" => "default"));            
        }
        
        if(!$series) {
            $series = $this->importSeries($mediaPackage);
        }

        $multimediaobjectsRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $onemultimediaobjects = $multimediaobjectsRepo->findOneBy(array("properties.opencast" => $mediaPackage["id"]));        

        if($onemultimediaobjects == null){
            $title = $mediaPackage["title"];
            $properties = $mediaPackage["id"];
            $recDate = $mediaPackage["start"];

            $multimediaObject = $this->factoryService->createMultimediaObject($series);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($recDate);
            $multimediaObject->setProperty("opencast", $properties);
            $multimediaObject->setProperty("opencasturl", $this->opencastClient->getPlayerUrl() . "?id=" . $properties);
            $multimediaObject->setTitle($title);
            if (isset($mediaPackage["language"])) $multimediaObject->setProperty("opencastlanguage", strtolower($mediaPackage["language"]));
            foreach($this->otherLocales as $locale) {
                $multimediaObject->setTitle($title, $locale);
            }

            //Multiple tracks
            if(isset($mediaPackage["media"]["track"][0])){
                for($i=0; $i<count($mediaPackage["media"]["track"]); $i++) {

                    $tags = $mediaPackage["media"]["track"][$i]["tags"];
                    $url = $mediaPackage["media"]["track"][$i]["url"];
                    $mime = $mediaPackage["media"]["track"][$i]["mimetype"];
                    $duration = $mediaPackage["media"]["track"][$i]["duration"];

                    $track = new Track();

                    if( isset($mediapackage["media"]["track"][$i]["audio"])) {
                        $acodec = $mediaPackage["media"]["track"][$i]["audio"]["encoder"]["type"];
                        $track->setAcodec($acodec);
                    }

                    if( isset($mediaPackage["media"]["track"][$i]["video"])) {
                        $vcodec = $mediaPackage["media"]["track"][$i]["video"]["encoder"]["type"];
                        $track->setVcodec($vcodec);
                        $framerate = $mediaPackage["media"]["track"][$i]["video"]["framerate"];
                        $track->setFramerate($framerate);
                    }

                    if (!$track->getVcodec() && $track->getAcodec()) {
                        $track->setOnlyAudio(true);
                    }

                    $track->addTag("opencast");
                    $track->addTag($mediaPackage["media"]["track"][$i]["type"]);
                    $track->setUrl($url);
                    $track->setPath($this->opencastService->getPath($url));
                    $track->setMimeType($mime);
                    $track->setDuration($duration/1000);

                    $this->inspectionService->autocompleteTrack($track);

                    $multimediaObject->addTrack($track);
                }
            } else {
                $tags = $mediaPackage["media"]["track"]["tags"];
                $url = $mediaPackage["media"]["track"]["url"];
                $mime = $mediaPackage["media"]["track"]["mimetype"];
                $duration = $mediaPackage["media"]["track"]["duration"];

                $track = new Track();

                if( isset($mediapackage["media"]["track"]["audio"])) {
                    $acodec = $mediaPackage["media"]["track"]["audio"]["encoder"]["type"];
                    $track->setAcodec($acodec);
                }

                if( isset($mediaPackage["media"]["track"]["video"])) {
                    $vcodec = $mediaPackage["media"]["track"]["video"]["encoder"]["type"];
                    $track->setVcodec($vcodec);
                    $framerate = $mediaPackage["media"]["track"][$i]["video"]["framerate"];
                    $track->setFramerate($framerate);
                }

                if (!$track->getVcodec() && $track->getAcodec()) {
                    $track->setOnlyAudio(true);
                }

                $track->addTag("opencast");
                $track->addTag($mediaPackage["media"]["track"]["type"]);
                $track->setUrl($url);
                $track->setPath($this->opencastService->getPath($url));
                $track->setMimeType($mime);
                $track->setDuration($duration/1000);

                $this->inspectionService->autocompleteTrack($track);

                $multimediaObject->addTrack($track);
            }

            for($j = 0; $j < count($mediaPackage["attachments"]["attachment"]); $j++){

                if($mediaPackage["attachments"]["attachment"][$j]["type"] == "presenter/search+preview"){

                    $tags = $mediaPackage["attachments"]["attachment"][$j]["tags"];
                    $url = $mediaPackage["attachments"]["attachment"][$j]["url"];

                    $pic = new Pic();
                    $pic->setTags(array($tags));
                    $pic->setUrl($url);

                    $multimediaObject->addPic($pic);
                }
            }

            $tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
            $opencastTag = $tagRepo->findOneByCod('TECHOPENCAST');
            if ($opencastTag) {
                $tagService = $this->tagService;
                $tagAdded = $tagService->addTagToMultimediaObject($multimediaObject, $opencastTag->getId());
            }
            $this->dm->persist($multimediaObject);
            $this->dm->flush();

            if($track) {
                $this->opencastService->genSbs($multimediaObject);
            }

        }
    }

    private function importSeries($mediaPackage)
    {
        $announce = true;
        $publicDate = new \DateTime("now");

        if(isset($mediaPackage["series"])){
            $title = $mediaPackage["seriestitle"];
            $properties = $mediaPackage["series"];            
        } else{
            $title = "MediaPackages without series";
            $properties = "default";            
        }

        $series = $this->factoryService->createSeries();
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        foreach($this->otherLocales as $locale) {
            $series->setTitle($title, $locale);
        }

        $series->setProperty("opencast", $properties);

        $this->dm->persist($series);
        $this->dm->flush();

        return $series;
    }
}
