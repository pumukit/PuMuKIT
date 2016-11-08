<?php

namespace Pumukit\ArcaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class IndexController extends Controller
{
    /**
     * db.MultimediaObject.aggregate([{$group: {_id: {$year: "$record_date"}}}]).
     *
     * @Route("/arca.xml", defaults={"_format": "xml"}, name="pumukit_arca_index")
     * @Template()
     */
    public function indexAction()
    {
        $mmObjColl = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array('$group' => array('_id' => array('$year' => '$record_date'))),
        );

        $years = $mmObjColl->aggregate($pipeline);

        return array('years' => $years);
    }

    /**
     * @Route("{year}/arca.xml", defaults={"_format": "xml"}, name="pumukit_arca_list")
     * @Template()
     */
    public function listAction($year)
    {
        $mmObjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $start = new \DateTime($year.'/01/01');
        $end = new \DateTime($year.'/12/31');

        $in_range = array('$gte' => $start, '$lt' => $end);

        $multimediaObjects = $mmObjRepo->findBy(array('record_date' => $in_range));

        return array('multimediaObjects' => $multimediaObjects);
    }
}
