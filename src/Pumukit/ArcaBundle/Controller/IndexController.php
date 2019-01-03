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
     *
     * @return array
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function indexAction()
    {
        $mmObjColl = $this->get('doctrine_mongodb.odm.document_manager')->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');

        $pipeline = array(
            array('$group' => array('_id' => array('$year' => '$record_date'))),
        );

        $years = $mmObjColl->aggregate($pipeline, array('cursor' => array()));

        return array('years' => $years);
    }

    /**
     * @Route("{year}/arca.xml", defaults={"_format": "xml"}, name="pumukit_arca_list")
     * @Template()
     *
     * @param string $year
     *
     * @return array
     *
     * @throws \Exception
     */
    public function listAction($year)
    {
        $start = new \DateTime($year.'/01/01');
        $end = new \DateTime($year.'/12/31');

        $in_range = array('$gte' => $start, '$lt' => $end);

        $multimediaObjects = $this->get('doctrine_mongodb.odm.document_manager')->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(array('record_date' => $in_range));

        return array('multimediaObjects' => $multimediaObjects);
    }
}
