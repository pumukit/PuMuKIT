<?php

namespace Pumukit\OpencastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\ArrayAdapter;
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
        $resources = $this->getResources($request, $config, $criteria);

        $pluralName = $config->getPluralResourceName();
        dump($pluralName);

        $mediaPackages = $this->get('pumukit_opencast.client')->getMediaPackages(0,0,0);
        dump($mediaPackages);

        $adapter = new ArrayAdapter($mediaPackages);
        $pagerfanta = new Pagerfanta($adapter);

        $limit = 5;
        $offset =  0;
        $page =  $request->get("page", 1);

    	$mediaPackages = $this->get('pumukit_opencast.client')->getMediaPackages(0,$limit,$offset);

        //$adapter = new ArrayAdapter($mediaPackages);
        //$pagerfanta = new Pagerfanta($adapter);

        //$pagerfanta->setMaxPerPage($limit);
        //$pagerfanta->setCurrentPage($offset);

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

        dump($new_criteria);

        return $new_criteria;
    }


    /**
     * Gets the list of resources according to a criteria
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();
        $session = $this->get('session');
        $session_namespace = 'admin/' . $config->getResourceName();

        if ($config->isPaginated()) {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'createPaginator', array($criteria, $sorting));

            if ($request->get('page', null)) {
                $session->set($session_namespace.'/page', $request->get('page', 1));
            }

            if ($request->get('paginate', null)) {
                $session->set($session_namespace.'/paginate', $request->get('paginate', 10));
            }

            $resources
                ->setCurrentPage($session->get($session_namespace.'/page', 1), true, true)
                ->setMaxPerPage($session->get($session_namespace.'/paginate', 10));
        } else {
            $resources = $this
                ->resourceResolver
                ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()));
        }

        return $resources;
    }


    /*public function downloadAction($mediaPackage)
    {
        dump($mediaPackage->getPath());

        $response = new BinaryFileResponse($mediaPackage);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
                                         ResponseHeaderBag::DISPOSITION_INLINE,
                                         basename($track->getPath()),
                                         iconv('UTF-8', 'ASCII//TRANSLIT', basename($track->getPath()))
                                         );

        return $response;
    }*/
}