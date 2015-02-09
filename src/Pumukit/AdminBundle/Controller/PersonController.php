<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\AdminBundle\Form\Type\PersonType;

class PersonController extends AdminController
{
    /**
     * Index
     *
     * TODO with Symfony Controller
     * @Template("PumukitAdminBundle:Person:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $config = $this->getConfiguration();

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = array();
        foreach($resources as $person){
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return array(
                     'people' => $resources,
                     'countMmPeople' => $countMmPeople,
                     );
    }

    /**
     * Create new person
     *
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
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }
        
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
     *
     * @Template("PumukitAdminBundle:Person:show.html.twig")
     */
    public function showAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

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

        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = array();
        foreach($resources as $person){
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return array(
                     'people' => $resources,
                     'countMmPeople' => $countMmPeople
                     );
    }

    /**  
     * Create new person with role from Multimedia Object
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:listautocomplete.html.twig")
     */
    public function listAutocompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $config = $this->getConfiguration();
        $pluralName = $config->getPluralResourceName();
        
        $criteria = $this->getCriteria($config);
        $resources = $this->getResources($request, $config, $criteria);

        return array(
                     'people' => $resources,
                     'mm' => $multimediaObject,
                     'role' => $role,
                     'prototypeStatus' => MultimediaObject::STATUS_PROTOTYPE
                     );
    }

    /**
     * Create relation
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:createrelation.html.twig")
     */
    public function createRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }
        
        $form = $this->createForm(new PersonType(), $person);
        
        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = '_template';
        }

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $personService = $this->get('pumukitschema.person');              
                $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            return $this->render('PumukitAdminBundle:Person:listrelation.html.twig', 
                                 array(
                                       'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                       'role' => $role,
                                       'mm' => $multimediaObject,
                                       'template' => $template
                                       ));
        }

        return array(
                     'person' => $person,
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'form' => $form->createView(),
                     'template' => $template
                     );
    }

    /**
     * Update relation
     *
     * @Template("PumukitAdminBundle:Person:updaterelation.html.twig")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function updateRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }
        
        $form = $this->createForm(new PersonType(), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $person = $personService->updatePerson($person);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            $template = '';
            if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
                $template = '_template';
            }

            return $this->render('PumukitAdminBundle:Person:listrelation.html.twig', 
                                 array(
                                       'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                       'role' => $role,
                                       'mm' => $multimediaObject,
                                       'template' => $template
                                       ));

        }

        return array(
                     'person' => $person,
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'form' => $form->createView()
                     );
    }

    /**
     * Link person to multimedia object with role
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:listrelation.html.twig")
     */
    public function linkAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

        try{
            $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
            // TODO
            //$message = sprintf($this->getContext()->getI18N()->__("Persona asociada correctamente a la plantilla con el rol \"%s\"."), $this->role->getName());
            //$msg_alert = array('info', $message);
        }catch(\Excepction $e){
            //$message = sprintf($this->getContext()->getI18N()->__("Persona ya asociada a la plantilla con el rol \"%s\"."), $this->role->getName());
            //$this->msg_alert = array('error', $message);          
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = '_template';
        }
        
        return array(
                     'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template
                     );
    }

    /**
     * Auto complete
     *
     * @Template("PumukitAdminBundle:Person:autocomplete.html.twig")
     */
    public function autoCompleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $name = $request->get('name');
        $people = $personService->autoCompletePeopleByName($name);

        return array('people' => $people, 'name' => $name);
    }

    /**
     * Up person in MultimediaObject
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:listrelation.html.twig")
     */
    public function upAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

        $multimediaObject = $personService->upPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = '_template';
        }
        
        return array(
                     'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template
                     );        
    }

    /**
     * Down person in MultimediaObject
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:listrelation.html.twig")
     */
    public function downAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

        $multimediaObject = $personService->downPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = '_template';
        }
        
        return array(
                     'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template
                     );
    }

    /**
     * Delete relation: EmbeddedPerson in Multimedia Object
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitAdminBundle:Person:listrelation.html.twig")
     */
    public function deleteRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

        $multimediaObject = $personService->deleteRelation($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = '_template';
        }
        
        return array(
                     'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                     'role' => $role,
                     'mm' => $multimediaObject,
                     'template' => $template
                     );
    }

    /**
     * Delete Person
     *
     * @Template("PumukitAdminBundle:Person:list.html")
     */
    public function deleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        if (!$person) {
            throw new NotFoundHttpException('Requested Person does not exist with this id: '.$request->get('id').'.');
        }

        try{
            if (0 === $personService->countMultimediaObjectsWithPerson($person)){
                $personService->deletePerson($person);
            }
        }catch (\Exception $e){
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());          
        }

        return $this->redirect($this->generateUrl('pumukitadmin_person_list'));
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
}
