<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;

class IndexController extends Controller
{
    /**
     * @Route("/", name="pumukit_webtv_index_index")
     * @Template()
     */
    public function indexAction()
    {
      return array();
    }

    /**
     * @Template()
     */
    public function infoAction()
    {
      return array();
    }

    /**
     * @Template()
     */
    public function categoriesAction()
    {
      return array();
    }


    /**
     * @Template()
     */
    public function mostviewedAction()
    {
      $repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $multimediaObjectsSortedByNumview = $repository->findStandardBy(array(), array('numview' => -1), 3, 0);
      return array('multimediaObjectsSortedByNumview' => $multimediaObjectsSortedByNumview);
    }

    /**
     * @Template()
     */
    public function recentlyaddedAction()
    {
      $last = $this->get('pumukitschema.announce')->getLast(3);
      return array('last' => $last);
    }

    /**
     * @Template()
     */
    public function newsAction()
    {
      return array();
    }
}
