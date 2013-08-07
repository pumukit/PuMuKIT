<?php

namespace Pumukit\TranscoBundle\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Pumukit\TranscoBundle\Form\CpuType;
use Pumukit\TranscoBundle\Entity\Cpu;

class DefaultController extends Controller
{
    /**
     * @Route("/") 
     * @Template("PumukitTranscoBundle:Default:index.html.twig")
     */
    public function indexAction(Request $request)
    {
         
      $cpu = new Cpu();  
      $form = $this->createForm(new CpuType(), $cpu); 
      $form->bind($request);
      
      /*
      $form = CpuForm::create($this->get('form.context'), 'cpu');
      $cpu = new Cpu();

      $form->bind($this->get('request'), $cpu);
*/
      if ($form->isValid()) {
	echo "valido " . $cpu->getIP();
	exit;
	//$->send();
      }
      

      return array('form' => $form);
    }
}
