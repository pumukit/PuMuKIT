<?php

namespace Pumukit\MoodleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Route("/pumoodle")
 */
class MoodleController extends Controller
{
    const PERFIL_ID = 2;

    public $embed_mini = false;

    /**
     * @Route("/index")
     */
    public function indexAction(Request $request)
    {
        $email = $request->get('professor_email');
        $ticket  = $request->get('ticket');
        $locale = $this->getLocale($request->get('lang'));

        $roleCode = $this->container->getParameter('pumukit_moodle.role');
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Series');
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');

        if ($professor = $this->findProfessorEmailTicket($email, $ticket, $roleCode)){
            $series = $seriesRepo->findByPersonIdAndRoleCod($professor->getId(), $roleCode);
            $numberMultimediaObjects = 0;
            $multimediaObjectsArray = array();
            $out = array();
            foreach ($series as $oneseries){
                $seriesTitle = $oneseries->getTitle($locale);
                $multimediaObjectsArray[$seriesTitle] = array();
                $multimediaObjects = $mmobjRepo->findBySeriesAndPersonIdWithRoleCod($oneseries, $professor->getId(), $roleCode);
                foreach ($multimediaObjects as $multimediaObject) {
                  $multimediaObjectTitle = $multimediaObject->getRecordDate()->format('Y-m-d') . ' ' . $multimediaObject->getTitle($locale);
                    if ($multimediaObject->getSubtitle($locale) != ''){
                        $multimediaObjectTitle .= " - " . $multimediaObject->getSubtitle($locale);
                    }
                    $multimediaObjectsArray[$seriesTitle][$multimediaObjectTitle] = $this->generateUrl('pumukit_moodle_moodle_embed', array('id' => $multimediaObject->getId(), 'lang' => $locale), true);
                    $numberMultimediaObjects++;
                }
            }
            
            $out['status']     = "OK";
            $out['status_txt'] = $numberMultimediaObjects;
            $out['out']        = $multimediaObjectsArray;
            
            return new JsonResponse($out, 200);
        } 
        
        $out['status'] = "ERROR";
        $out['status_txt'] = "Authentication error: professor with email " . $email  . " not found in Pumukit video server.";
        $out['out'] = null;
        
        return new JsonResponse($out, 404);
    }
    
    /**
     * @Route("/repository")
     */
    public function repositoryAction(Request $request)
    {
    
    }


    /**
     * @Route("/embed", name="pumukit_moodle_moodle_embed")
     */
    public function embedAction(Request $request)
    {
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $id = $request->get('id');
        $locale = $this->getLocale($request->get('lang'));
        $multimediaObject = $mmobjRepo->find($id);
        $email = $request->get('professor_email');
        $ticket = $request->get('ticket');

        if ($multimediaObject) {
            if ($this->checkFieldTicket($email, $ticket, $id) ) {
                return $this->renderIframe($multimediaObject, $request);
            } else {
                $contactEmail = $this->container->getParameter('pumukit2.info')['email'];
                return $this->render('PumukitMoodleBundle:Moodle:403forbidden.html.twig',
                                   array('email' => $contactEmail, 'moodle_locale' => $locale));
            }
        }
        return $this->render('PumukitMoodleBundle:Moodle:404notfound.html.twig',
                             array('id' => $id, 'moodle_locale' => $locale));
    }

   /**
     * Render iframe
     */
    private function renderIframe(MultimediaObject $multimediaObject, Request $request)
    {
        if ($multimediaObject->getProperty('opencast')){
            /* $this->incNumView($multimediaObject); */
            /* $this->dispatch($multimediaObject); */
            $userAgent = $this->getRequest()->headers->get('user-agent');
            $mobileDetectorService = $this->get('mobile_detect.mobile_detector');
            $mobileDevice = ($mobileDetectorService->isMobile($userAgent) || $mobileDetectorService->isTablet($userAgent));
            $isOldBrowser = $this->getIsOldBrowser($userAgent);
            $track = $multimediaObject->getTrackWithTag('sbs');

            return $this->render("PumukitMoodleBundle:Moodle:opencastiframe.html.twig",
                                 array(
                                       "multimediaObject" => $multimediaObject,
                                       "track" => $track,
                                       "is_old_browser" => $isOldBrowser,
                                       "mobile_device" => $mobileDevice
                                       )
                                 );
        } else {
            $track = $request->query->has('track_id') ?
              $multimediaObject->getTrackById($request->query->get('track_id')) :
              $multimediaObject->getFilteredTrackWithTags(array('display'));
        }
        if (!$track)
            throw $this->createNotFoundException();

        //$this->incNumView($multimediaObject, $track);
        //$this->dispatch($multimediaObject, $track);

        return $this->render("PumukitMoodleBundle:Moodle:iframe.html.twig",
                             array('autostart' => $request->query->get('autostart', 'false'),
                                   'intro' => false,
                                   'multimediaObject' => $multimediaObject,
                                   'track' => $track));
    }

    private function checkFieldTicket($email, $ticket, $id='')
    {
        $check = "";
        $password = $this->container->getParameter('pumukit_moodle.password');
        $check = md5($password . date("Y-m-d") . $id . $email);
        return ($check === $ticket);
    }

    private function pumukit_curl_action_parameters($action, array $parameters = null, $absoluteurl = false)
    {
    }

    private function findProfessorEmailTicket($email, $ticket, $roleCode)
    {
        $repo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Person');

        $professor = $repo->findByRoleCodAndEmail($roleCode, $email);
        if ($this->checkFieldTicket($email, $ticket)) {
            return $professor;
        }
        return null;
    }

    private function getLocale($queryLocale)
    {
        $locale = strtolower($queryLocale);
        $defaultLocale = $this->container->getParameter('locale');
        $pumukitLocales = $this->container->getParameter('pumukit2.locales');
        if ((!$locale) || (!in_array($locale, $pumukitLocales))) {
            $locale = $defaultLocale;
        }
        return $locale;
    }

    private function getIsOldBrowser($userAgent)
    {
        $isOldBrowser = false;
        $webExplorer = $this->getWebExplorer($userAgent);
        $version = $this->getVersion($userAgent, $webExplorer);
        if (($webExplorer == 'IE') || ($webExplorer == 'MSIE') || $webExplorer == 'Firefox' || $webExplorer == 'Opera' || ($webExplorer == 'Safari' && $version<4)){
            $isOldBrowser = true;
        }

        return $isOldBrowser;
    }

    private function getWebExplorer($userAgent)
    {
        if (preg_match('/MSIE/i', $userAgent))         $webExplorer = "MSIE";
        if (preg_match('/Opera/i', $userAgent))        $webExplorer = 'Opera';
        if (preg_match('/Firefox/i', $userAgent))      $webExplorer = 'Firefox';
        if (preg_match('/Safari/i', $userAgent))       $webExplorer = 'Safari';
        if (preg_match('/Chrome/i', $userAgent))       $webExplorer = 'Chrome';

        return $webExplorer;
    }

    private function getVersion($userAgent, $webExplorer)
    {
        $version = null;

        if($webExplorer!=='Opera' && preg_match("#(".strtolower($webExplorer).")[/ ]?([0-9.]*)#", $userAgent, $match))
            $version = floor($match[2]);
        if($webExplorer=='Opera' || $webExplorer=='Safari' && preg_match("#(version)[/ ]?([0-9.]*)#", $userAgent, $match))
            $version = floor($match[2]);

        return $version;
    }
}
