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
     * @Route("/")
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
      $repository = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $multimediaObjectsSortedByPublicDate = $repository->findStandardBy(array(), array('public_date' => -1), 3, 0);
      return array('multimediaObjectsSortedByPublicDate' => $multimediaObjectsSortedByPublicDate);
    }

    /**
     * @Template()
     */
    public function newsAction()
    {
      return array();
    }
}
