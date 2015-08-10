<?php

namespace Pumukit\MoodleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


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
        $culture = $request->get('lang');

        $this->setCultureIfPresent($culture);

        if ($professor = $this->findProfessorEmailTicket($email, $ticket)){
            // TODO - CONTINUE IN HERE
            $series = $professor->getSeries(self::ROLE_ID);
            $total_mm = 0;
            $array_series_mm = array();
            $out = array();
          
            foreach ($series as $s){
                $serial_title = $s->getTitle();
                $array_series_mm[$serial_title] = array();
                $mms = $s->getMmsByPerson($professor->getId(), self::ROLE_ID);
        
                foreach ($mms as $mm) {

                  $mm_title = $mm->getRecordDate('Y-m-d') . ' ' . $mm->getTitle();
                  if ($mm->getSubtitle() != ''){
                    $mm_title .= " - " . $mm->getSubtitle();
                  }
                  $array_series_mm[$serial_title][$mm_title] = url_for('pumoodle/embed?m=' . $mm->getId(), true) . '/lang/' . $this->vculture;
                  $total_mm++;
                }
            }
            
            $out['status']     = "OK";
            $out['status_txt'] = $total_mm;
            $out['out']        = $array_series_mm;
            
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
     * @Route("/embed")
     */
    public function embedAction(Request $request)
    {
      
    }

    private function checkEmailTicket($email, $ticket)
    {
      $check = "";
      $password = $this->container->getParameter('pumukitmoodle.password');
      $check = md5($password . date("Y-m-d") . $email);
      return ($check === $ticket);
    }

    private function pumukit_curl_action_parameters($action, array $parameters = null, $absoluteurl = false)
    {
    }

    private function findProfessorEmailTicket($email, $ticket)
    {
        $roleCode = $this->container->getParameter('pumukitmoodle.actor');
        $repo = $this->get('doctrine_mongodb.odm.document_manager')
        ->getRepository('PumukitSchemaBundle:Person');

        $professor = $repo->findOneByCodAndEmail($roleCode, $email);
        if ($this->checkEmailTicket($email, $ticket)) {
            return $professor;
        }
        return null;
    }

    private function setCultureIfPresent($culture)
    {
        // TODO
    }
}
