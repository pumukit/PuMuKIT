<?php

namespace Pumukit\TranscoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Pumukit\TranscoBundle\Form\CpuForm;
use Pumukit\TranscoBundle\Entity\Cpu;

class DefaultController extends Controller
{
    /**
     *  @Route("/") 
     */
    public function indexAction()
    {

      $form = CpuForm::create($this->get('form.context'), 'cpu');
      $cpu = new Cpu();

      $form->bind($this->get('request'), $cpu);

      if ($form->isValid()) {
	echo "valido " . $cpu->getIP();
	exit;
	//$->send();
      }
      

      return $this->render('PumukitTranscoBundle:Default:index.html.twig', array('form' => $form));
    }
}
