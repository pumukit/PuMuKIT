<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class CollectionController extends Controller implements NewAdminController
{
    /**
     * @Template()
     */
    public function showAction(Series $collection, Request $request)
    {
        $this->get('session')->set('admin/collection/id', $collection->getId());
        return array('collection' => $collection);
    }
}
