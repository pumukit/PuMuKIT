<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\AdminBundle\Form\Type\PersonType;

class PersonController extends AdminController
{
    /**
     * Index TODO with Controller
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $pluralName = $config->getPluralResourceName();

        $view = $this
            ->view()
            ->setTemplate($config->getTemplate('index.html'))
            ->setTemplateVar($pluralName)
            ->setData($resources)
        ;

        return $this->handleView($view);
    }

    /**
     * Create new person
     * @Template("PumukitAdminBundle:Person:create.html.twig")
     */
    public function createAction(Request $request)
    {
        $person = new Person();
        $form = $this->createForm(new PersonType(), $person);

        return array(
                     'person' => $person,
                     'form' => $form->createView()
                     );
    }

    /**  
     * Create new person with role from Multimedia Object
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function listAutocompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();
        
        //$role = $this->getResourceFromId('Role', $request->get('roleId'));
        //$multimediaObject = $this->getResourceFromId('MultimediaObject', $request->get('mmId'));
        
        // TODO complete functionally

        
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);
        
        $view = $this
          ->view()
          ->setTemplate($config->getTemplate('listautocomplete.html'))
          ->setData(array(
                          'persons' => $resources,
                          'mm' => $multimediaObject,
                          'role' => $role,
                          ));
        
        return $this->handleView($view);
    }
    
    /**
     * Get resource from id
     */
    private function getResourceFromId($className, $id)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repository = $dm->getRepository('PumukitSchemaBundle:'.$className);
        
        $resource = $repository->find($id);
        
        return $resource;
    }
}
