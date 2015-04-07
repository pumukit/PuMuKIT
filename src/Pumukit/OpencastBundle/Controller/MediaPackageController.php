<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
//use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\SchemaBundle\Document\Pic;
use Symfony\Component\HttpFoundation\File\UploadedFile;
//use UploadedFile;

class MediaPackageController extends Controller
{
    private $dm = null;

    /**
     * @Route("/mediapackage", name="opencastimport")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $limit = 10;
        $page =  $request->get("page", 1);
        $criteria = $this->getCriteria($request);


        list($total, $mediaPackages) = $this->get('pumukit_opencast.client')->getMediaPackages(
                (isset($criteria["name"])) ? $criteria["name"]->regex : 0,
                $limit,
                ($page -1) * $limit);

        $adapter = new FixedAdapter($total, $mediaPackages);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return array('mediaPackages' => $pagerfanta);
    }


    /**
     * @Route("/mediapackage/{id}")
     */
    public function importAction($id, Request $request)
    {
        $mediaPackage = $this->get('pumukit_opencast.client')->getMediaPackage($id);

        $this->dm = $this->get('doctrine_mongodb')->getManager();
        $factoryService = $this->get('pumukitschema.factory');

        $announce = true;
        $publicDate = new \DateTime("now");
        $title = $mediaPackage["seriestitle"];
        $subtitle = '';
        $description = '';
        $header = '';
        $footer = '';
        $copyright = '';
        $keyword = '';
        $line2 = '';
        $locale = 'en';
        //$properties = $mediaPackage["series"];

        $series = $factoryService->createSeries();
        $series->setAnnounce($announce);
        $series->setPublicDate($publicDate);
        $series->setTitle($title);
        $series->setSubtitle($subtitle);
        $series->setDescription($description);
        $series->setHeader($header);
        $series->setFooter($footer);
        $series->setCopyright($copyright);
        $series->setKeyword($keyword);
        $series->setLine2($line2);
        $series->setLocale($locale);
        //$series->setProperty("opencast",$properties);

        $titleEs = $mediaPackage["seriestitle"];
        $subtitleEs = '';
        $descriptionEs = '';
        $headerEs = '';
        $footerEs = '';
        $copyrightEs = '';
        $keywordEs = '';
        $line2Es = '';
        $localeEs = 'es';

        $titleI18n = array($locale => $title, $localeEs => $titleEs);
        $subtitleI18n = array($locale => $subtitle, $localeEs => $subtitleEs);
        $descriptionI18n = array($locale => $description, $localeEs => $descriptionEs);
        $headerI18n = array($locale => $header, $localeEs => $headerEs);
        $footerI18n = array($locale => $footer, $localeEs => $footerEs);
        $copyrightI18n = array($locale => $copyright, $localeEs => $copyrightEs);
        $keywordI18n = array($locale => $keyword, $localeEs => $keywordEs);
        $line2I18n = array($locale => $line2, $localeEs => $line2Es);

        $series->setI18nTitle($titleI18n);
        $series->setI18nSubtitle($subtitleI18n);
        $series->setI18nDescription($descriptionI18n);
        $series->setI18nHeader($headerI18n);
        $series->setI18nFooter($footerI18n);
        $series->setI18nCopyright($copyrightI18n);
        $series->setI18nKeyword($keywordI18n);
        $series->setI18nLine2($line2I18n);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($series);
        $dm->flush();

        $rank = 3;
        $status = MultimediaObject::STATUS_NORMAL;
        $title = $mediaPackage["title"];

        $multimediaObject =  $factoryService->createMultimediaObject($series);
        $multimediaObject->setRank($rank);
        $multimediaObject->setStatus($status);
        $multimediaObject->setSeries($series);
        $multimediaObject->setTitle($title);

        for($i=0; $i<count($mediaPackage["media"]["track"]); $i++){

            $tags = $mediaPackage["media"]["track"][$i]["tags"];
            $url = $mediaPackage["media"]["track"][$i]["url"];
            $mime = $mediaPackage["media"]["track"][$i]["mimetype"];
            $duration = $mediaPackage["media"]["track"][$i]["duration"];
            $acodec = $mediaPackage["media"]["track"][$i]["audio"]["encoder"]["type"];
            $vcodec = $mediaPackage["media"]["track"][$i]["video"]["encoder"]["type"];
         
            $track = new Track();
            $track->setTags(array("opencast"));
            $track->setUrl($url);
            $track->setMimeType($mime);
            $track->setDuration($duration);
            $track->setAcodec($acodec);
            $track->setVcodec($vcodec);

            $multimediaObject->addTrack($track);
        }


        for($j=0; $j<count($mediaPackage["attachments"]["attachment"]); $j++){

            if($mediaPackage["attachments"]["attachment"][$j]["type"] == "presenter/search+preview"){

                $tags = $mediaPackage["attachments"]["attachment"][$j]["tags"];
                $url = $mediaPackage["attachments"]["attachment"][$j]["url"];

                $pic = new Pic();
                $pic->setTags(array($tags));
                $pic->setUrl($url);

                $multimediaObject->addPic($pic);
            }
        }


        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($multimediaObject);
        $dm->flush();

        return $this->redirectToRoute('opencastimport');
    }


    /**
     * Gets the criteria values
     */
    public function getCriteria($request)
    {
        $criteria = $request->get('criteria', array());


        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/opencast/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/opencast/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/opencast/criteria', array());

        $new_criteria = array();

        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }
}