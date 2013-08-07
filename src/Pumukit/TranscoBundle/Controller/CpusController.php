<?php

/**
 * Description of CpusController
 *
 * @author Ivan Vazquez <ivan@teltek.es>
 */

namespace Pumukit\TranscoBundle\Controller;

use Pumukit\TranscoBundle\Entity\Cpu;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class CpusController extends Controller {

    /**
     * @return array
     * @View()
     */
    public function getCpusAction()
    {
        $cpus = $this->getDoctrine()->getRepository('PumukitTranscoBundle:Cpu')->findAll();
        
        return array('cpus' => $cpus);
    }
    
    /**
     * @param Cpu $cpu
     * @return array
     * @View()
     * @ParamConverter("cpu", class="PumukitTranscoBundle:Cpu")
     */
    public function getCpuAction(Cpu $cpu)
    {
        return array('cpu' => $cpu);
    }
    
}

?>
