<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class MediaPackageController extends ResourceController
{
    /**
     * @Route("/mediapackage")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();
        $criteria = $this->getCriteria($config);

        $pluralName = $config->getPluralResourceName();

        $limit = 10;
        $page =  $request->get("page", 1);


        list($total, $mediaPackages) = $this->get('pumukit_opencast.client')->getMediaPackages(
                (isset($criteria["name"])) ? $criteria["name"]->regex : 0,
                $limit,
                ($page -1) * $limit);

        $adapter = new FixedAdapter($total, $mediaPackages);
        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return array('mediaPackages' => $pagerfanta);
    }


    /**
     * Gets the criteria values
     */
    public function getCriteria($config)
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/'.$config->getResourceName().'/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/'.$config->getResourceName().'/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/'.$config->getResourceName().'/criteria', array());

        $new_criteria = array();

        foreach ($criteria as $property => $value) {
            //preg_match('/^\/.*?\/[imxlsu]*$/i', $e)
            if ('' !== $value) {
                $new_criteria[$property] = new \MongoRegex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }

}