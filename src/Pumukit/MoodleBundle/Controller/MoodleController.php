<?php

namespace Pumukit\MoodleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/pumoodle")
 */
class MoodleController extends Controller
{
    const ROLE_ID = 1;
    const SECRET = '123_U?R';
    const PERFIL_ID = 2;

    public $embed_mini = false;

    /**
     * @Route("/index")
     */
    public function indexAction(Request $request)
    {
        $email = $request->get('professor_email');
        $ticket  = $request->get('ticket');
        $locale = $request->get('lang', $this->get('session')->get('_locale'));

        $roleCode = $this->container->getParameter('pumukit_moodle.role');
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Series');
        $mmobjRepo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:MultimediaObject');

        $this->setLocaleIfPresent($locale);

        if ($professor = $this->findProfessorEmailTicket($email, $ticket, $roleCode)){
            $series = $seriesRepo->findByPersonIdAndRoleCod($professor->getId(), $roleCode);
            $numberMultimediaObjects = 0;
            $multimediaObjectsArray = array();
            $out = array();
            foreach ($series as $oneseries){
                $seriesTitle = $oneseries->getTitle();
                $multimediaObjectsArray[$seriesTitle] = array();
                $multimediaObjects = $mmobjRepo->findBySeriesAndPersonIdWithRoleCod($oneseries, $professor->getId(), $roleCode);
                foreach ($multimediaObjects as $multimediaObject) {
                  $multimediaObjectTitle = $multimediaObject->getRecordDate()->format('Y-m-d') . ' ' . $multimediaObject->getTitle();
                    if ($multimediaObject->getSubtitle() != ''){
                        $multimediaObjectTitle .= " - " . $multimediaObject->getSubtitle();
                    }
                    $multimediaObjectsArray[$seriesTitle][$multimediaObjectTitle] = $this->generateUrl('pumukit_moodle_pumoodle_embed', array('multimediaObject' => $multimediaObject->getId(), 'lang' => $locale), true);
                    $numberMultimediaObjects++;
                }
            }
            
            $out['status']     = "OK";
            $out['status_txt'] = $numberMultimediaObjects;
            $out['out']        = $multimediaObjectsArray;
            
            return new JsonResponse($out, 200);          
        } 
        
        $out['status'] = "ERROR";
        $out['status_txt'] = "Error de autenticación - profesor no encontrado en el servidor de vídeo pumukit";
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
     * @Route("/embed", name="pumukit_moodle_pumoodle_embed")
     */
    public function embedAction(Request $request)
    {
        $multimediaObject = $request->get('multimediaObject');
        $locale = $request->get('lang');
    }

    private function checkEmailTicket($email, $ticket)
    {
      $check = "";
      $password = $this->container->getParameter('pumukit_moodle.password');
      $check = md5($password . date("Y-m-d") . $email);
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
        if ($this->checkEmailTicket($email, $ticket)) {
            return $professor;
        }
        return null;
    }

    private function setLocaleIfPresent($locale)
    {
        $session = $this->get('session');
        $previousLocale = $session->get('_locale');
        try {
            $session->set('_locale', $locale);
        } catch (\Exception $e) {
            $session->set('_locale', $previousLocale);
        }
    }
}
