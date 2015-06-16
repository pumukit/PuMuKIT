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


class OpencastImportService
{
    private $opencastClient;
    private $dm;
    private $factoryService;
    private $tagService;
    private $jobService;
    private $otherLocales;
    
    public function __construct(DocumentManager $documentManager, factoryService $factoryService,
                                tagService $tagService, ClientService $opencastClient, OpencastService $jobService,
                                array $otherLocales = array()) {
        $this->opencastClient = $opencastClient;
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->jobService = $jobService;
        $this->otherLocales = $otherLocales;
    }


    public function importRecording($opencastId)
    {
        $opencastClient = $this->opencastClient;
        $oneseries = "WITHOUT_SERIES";

        $mediaPackage = $opencastClient->getMediaPackage($opencastId);
        $repository_series = $this->dm->getRepository('PumukitSchemaBundle:Series');

        $series = $repository_series->findOneBy(array("properties.opencast" => "default"));

        if(isset($mediaPackage["series"])){
            $oneseries = $repository_series->findOneBy(array("properties.opencast" => $mediaPackage["series"]));
        }
        $repository_multimediaobjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $onemultimediaobjects = $repository_multimediaobjects->findOneBy(array("properties.opencast" => $mediaPackage["id"]));

        $factoryService = $this->factoryService;

        if(!$oneseries || ($oneseries == "WITHOUT_SERIES" && !$series)){
            $this->importSeries($oneseries, $mediaPackage);
        }

        if($onemultimediaobjects == null){
            $title = $mediaPackage["title"];
            $properties = $mediaPackage["id"];
            $recDate = $mediaPackage["start"];

            if($oneseries != "WITHOUT_SERIES"){
                $series = $repository_series->findOneBy(array("properties.opencast" => $mediaPackage["series"]));
            }

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setSeries($series);
            $multimediaObject->setRecordDate($recDate);
            $multimediaObject->setProperty("opencast", $properties);
            $multimediaObject->setProperty("opencasturl", $opencastClient->getPlayerUrl() . "?id=" . $properties);
            $multimediaObject->setTitle($title);
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
                    }

                    if (!$track->getVcodec() && $track->getAcodec()) {
                        $track->setOnlyAudio(true);
                    }

                    $track->addTag("opencast");
                    $track->addTag($mediaPackage["media"]["track"][$i]["type"]);
                    $track->setUrl($url);
                    $track->setPath($this->jobService->getPath($url));
                    $track->setMimeType($mime);
                    $track->setDuration($duration/1000);

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
                }

                if (!$track->getVcodec() && $track->getAcodec()) {
                    $track->setOnlyAudio(true);
                }

                $track->addTag("opencast");
                $track->addTag($mediaPackage["media"]["track"]["type"]);
                $track->setUrl($url);
                $track->setPath($this->jobService->getPath($url));
                $track->setMimeType($mime);
                $track->setDuration($duration/1000);

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

            $dm = $this->dm;
            $tagRepo = $dm->getRepository('PumukitSchemaBundle:Tag');
            $opencastTag = $tagRepo->findOneByCod('TECHOPENCAST');
            if ($opencastTag) {
                $tagService = $this->tagService;
                $tagAdded = $tagService->addTagToMultimediaObject($multimediaObject, $opencastTag->getId());
            }
            $dm->persist($multimediaObject);
            $dm->flush();

            if($track) {
                $this->jobService->genSbs($multimediaObject);
            }

        }
    }

    private function importSeries($oneseries, $mediaPackage)
    {
        $announce = true;
        $publicDate = new \DateTime("now");

        if($oneseries == "WITHOUT_SERIES"){
            $title = "MediaPackages without series";
            $properties = "default";
        } else{
            $title = $mediaPackage["seriestitle"];
            $properties = $mediaPackage["series"];
        }

        $series = $this->factoryService->createSeries();
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        foreach($this->otherLocales as $locale) {
            $series->setTitle($title, $locale);
        }

        $series->setProperty("opencast", $properties);

        $dm = $this->dm;
        $dm->persist($series);
        $dm->flush();
    }
}
