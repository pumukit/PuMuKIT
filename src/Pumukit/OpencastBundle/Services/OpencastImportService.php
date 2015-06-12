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
    public function __construct(DocumentManager $documentManager, factoryService $factoryService,
            tagService $tagService, ClientService $opencastClient, OpencastService $jobService) {
        $this->opencastClient = $opencastClient;
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->jobService = $jobService;
    }


    public function importRecording($opencastId)
    {
        $opencastClient = $this->opencastClient;
        $oneseries = "WITHOUT_SERIES";

        $mediaPackage = $opencastClient->getMediaPackage($opencastId);
        $repository_series = $this->dm->getRepository('PumukitSchemaBundle:Series');

        $series = $repository_series->findOneBy(array("title.en" => "MediaPackages without series"));

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

            $rank = 3;
            $status = MultimediaObject::STATUS_PUBLISHED;
            $title = $mediaPackage["title"];
            $properties = $mediaPackage["id"];
            $recDate = $mediaPackage["start"];

            if($oneseries != "WITHOUT_SERIES"){
                $series = $repository_series->findOneBy(array("properties.opencast" => $mediaPackage["series"]));
            }

            $multimediaObject = $factoryService->createMultimediaObject($series);
            $multimediaObject->setRank($rank);
            $multimediaObject->setStatus($status);
            $multimediaObject->setSeries($series);
            $multimediaObject->setTitle($title);
            $multimediaObject->setRecordDate($recDate);
            $multimediaObject->setProperty("opencast", $properties);
            $multimediaObject->setProperty("opencasturl", $opencastClient->getPlayerUrl() . "?id=" . $properties);

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
        $locale = 'en';

        if($oneseries == "WITHOUT_SERIES"){
            $title = "MediaPackages without series";
            $properties = "";
        } else{
            $title = $mediaPackage["seriestitle"];
            $properties = $mediaPackage["series"];
        }

        $series = $this->factoryService->createSeries();
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        $series->setLocale($locale);
        $series->setProperty("opencast", $properties);

        $subtitleEs = '';
        $descriptionEs = '';
        $headerEs = '';
        $footerEs = '';
        $keywordEs = '';
        $line2Es = '';
        $localeEs = 'es';

        if($oneseries == "WITHOUT_SERIES"){
            $titleEs = "Paquetes multimedia sin serie";
            $properties = "";
        } else{
            $titleEs = $mediaPackage["seriestitle"];
        }

        $titleI18n = array($locale => $title, $localeEs => $titleEs);
        $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
        $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
        $headerI18n = array($locale => $header, $localeEs => $headerEs);
        $footerI18n = array($locale => $footer, $localeEs => $footerEs);
        $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
        $line2I18n = array($locale => $line2, $localeEs => $line2Es);

        $series->setI18nTitle($titleI18n);
        $series->setI18nSubtitle($subtitleI18n);
        $series->setI18nDescription($descriptionI18n);
        $series->setI18nHeader($headerI18n);
        $series->setI18nFooter($footerI18n);
        $series->setI18nKeyword($keywordI18n);
        $series->setI18nLine2($line2I18n);

        $dm = $this->dm;
        $dm->persist($series);
        $dm->flush();
    }
}
