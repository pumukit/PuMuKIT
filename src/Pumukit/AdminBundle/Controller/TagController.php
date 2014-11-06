<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


use Pumukit\SchemaBundle\Document\Tag;

class TagController extends Controller
{

    /**
     * Overwrite to update the criteria with MongoRegex, and save it in the session
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

      //TODO ROOT
      $root_name = "UNESCO";
      $root = $repo->findOneByCod($root_name);

      
      
      return array('root' => $root,
		   'childrens' => $root->getChildren());
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function childrenAction(Tag $tag, Request $request)
    {      
      return array('tag' => $tag,
		   'childrens' => $tag->getChildren());
    }

}