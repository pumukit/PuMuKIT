<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
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
        $personService = $this->get('pumukitschema.person');

        $person = new Person();
        $form = $this->createForm(new PersonType(), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $person = $personService->savePerson($person);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('pumukitadmin_person_index'));
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView()
                     );
    }

    /**
     * Update person
     * // TODO WITH symfony CONTROLLER @ParamConverter("person", class="PumukitSchemaBundle:Person")
     * @Template("PumukitAdminBundle:Person:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        
        $form = $this->createForm(new PersonType(), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $person = $personService->updatePerson($person);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->redirect($this->generateUrl('pumukitadmin_person_index'));
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView()
                     );
    }

    /**
     * Show person
     * @Template("PumukitAdminBundle:Person:show.html.twig")
     */
    public function showAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $limit = 5;
        $series = $personService->findSeriesWithPerson($person, $limit);

        return array(
                     'person' => $person,
                     'series' => $series
                     );
    }

    /**
     * List people
     *
     * @Template("PumukitAdminBundle:Person:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return array('people' => $resources);
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
        
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);
        
        $view = $this
          ->view()
          ->setTemplate($config->getTemplate('listautocomplete.html'))
          ->setData(array(
                          'people' => $resources,
                          'mm' => $multimediaObject,
                          'role' => $role,
                          ));
        
        return $this->handleView($view);
    }

    /**
     * Create relation
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:updaterelation.html.twig")
     */
    public function createRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));
        
        $form = $this->createForm(new PersonType(), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            $template = '';
            if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
                $template = 'template';
            }

            return $this->render('PumukitAdminBundle:Person:listrelation'.$template.'.html.twig', 
                                 array(
                                       'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                       'role' => $role,
                                       'mm' => $multimediaObject
                                       ));
        }

        return array(
                     'person' => $person,
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'form' => $form->createView(),
                     );
    }

    /**
     * Gets the criteria values
     */
    public function getCriteria($config)
    {
        $criteria = $config->getCriteria();

        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/person/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/person/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/person/criteria', array());
        
        //TODO: do upstream
        $new_criteria = array();

        if (array_key_exists('name', $criteria) && array_key_exists('letter', $criteria)){
            if (('' !== $criteria['name']) && ('' !== $criteria['letter'])){
                // TODO - regex multiple conditions with and
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'|^'.$criteria['letter'].'/i');
            }elseif('' !== $criteria['name']){
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            }elseif('' !== $criteria['letter']){
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        }elseif(array_key_exists('name', $criteria)){
            if ('' !== $criteria['name']){
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            }

        }elseif(array_key_exists('letter', $criteria)){
            if ('' !== $criteria['letter']){
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        }

        // TODO locale
        if (array_key_exists('other.en', $criteria)){
            if ('' !== $criteria['other.en']){
                $other = explode(' ', $criteria['other.en']);
                $value = implode('|', array_filter($other));
                $new_criteria['post.en'] = new \MongoRex('/'.$value.'/i');
                $new_criteria['firm.en'] = new \MongoRex('/'.$value.'/i');
                $new_criteria['bio.en'] = new \MongoRex('/'.$value.'/i');
            }
        }

        return $new_criteria;
    }
    
    /**
     * Gets the list of resources according to a criteria
     */
    public function getResources(Request $request, $config, $criteria)
    {
        $sorting = $config->getSorting();
        $repository = $this->getRepository();
        
        if ($config->isPaginated()) {
            $resources = $this
              ->resourceResolver
              ->getResource($repository, 'createPaginator', array($criteria, $sorting))
              ;
            
            if ($request->get('page', null)) {
                $this->get('session')->set('admin/person/page', $request->get('page', 1));
            }
            
            $resources
              ->setCurrentPage($this->get('session')->get('admin/person/page', 1), true, true)
              ->setMaxPerPage($config->getPaginationMaxPerPage())
              ;
        } else {
            $resources = $this
              ->resourceResolver
              ->getResource($repository, 'findBy', array($criteria, $sorting, $config->getLimit()))
              ;
        }
        
        return $resources;
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
