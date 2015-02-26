<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\NewAdminBundle\Form\Type\PersonType;

class PersonController extends AdminController
{
    /**
     * Index
     *
     * TODO with Symfony Controller
     * @Template("PumukitNewAdminBundle:Person:index.html.twig")
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
     * @Template("PumukitNewAdminBundle:Person:create.html.twig")
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

            return $this->redirect($this->generateUrl('pumukitnewadmin_person_index'));
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView()
                     );
    }

    /**
     * Update person
     * // TODO WITH symfony CONTROLLER @ParamConverter("person", class="PumukitSchemaBundle:Person")
     * @Template("PumukitNewAdminBundle:Person:update.html.twig")
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

            return $this->redirect($this->generateUrl('pumukitnewadmin_person_index'));
        }

        return array(
                     'person' => $person,
                     'form' => $form->createView()
                     );
    }

    /**
     * Show person
     *
     * @Template("PumukitNewAdminBundle:Person:show.html.twig")
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
     * @Template("PumukitNewAdminBundle:Person:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $config = $this->getConfiguration();

        $sorting = $request->get('sorting');
        if (null !== $sorting){
            $this->get('session')->set('admin/person/type', $sorting[key($sorting)]);
            $this->get('session')->set('admin/person/sort', key($sorting));
        }

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
     * @Template("PumukitNewAdminBundle:Person:listautocomplete.html.twig")
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
                     'role' => $role
                     );
    }

    /**
     * Create relation
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:createrelation.html.twig")
     */
    public function createRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));
        
        $form = $this->createForm(new PersonType(), $person);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $personService = $this->get('pumukitschema.person');              
                $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
            } catch (\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }

            $template = '';
            if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
                $template = 'template';
            }

            return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
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
     * Update relation
     *
     * @Template("PumukitNewAdminBundle:Person:updaterelation.html.twig")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function updateRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
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

            $template = '';
            if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
                $template = 'template';
            }

            return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
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
                     'form' => $form->createView()
                     );
    }

    /**
     * Link person to multimedia object with role
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     *
     */
    public function linkAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
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
            $template = 'template';
        }
        
        return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
                             array(
                                   'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                   'role' => $role,
                                   'mm' => $multimediaObject
                                   ));
    }

    /**
     * Auto complete
     */
    public function autoCompleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $name = $request->get('term');
        $people = $personService->autoCompletePeopleByName($name);

        $out = [];
        foreach($people as $p){
            $out[] = array(
                           "id"=> $p->getId(),
                           "label"=> $p->getName(),
                           "desc" => $p->getPost()." ". $p->getFirm(),
                           "value" => $p->getName()
                           );

        }

        return new JsonResponse($out);
    }

    /**
     * Up person in MultimediaObject
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->upPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = 'template';
        }
        
        return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
                             array(
                                   'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                   'role' => $role,
                                   'mm' => $multimediaObject
                                   ));        
    }

    /**
     * Down person in MultimediaObject
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->downPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = 'template';
        }
        
        return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
                             array(
                                   'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                   'role' => $role,
                                   'mm' => $multimediaObject
                                   ));
    }    

    /**
     * Delete relation: EmbeddedPerson in Multimedia Object
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function deleteRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $multimediaObject = $personService->deleteRelation($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()){
            $template = 'template';
        }
        
        return $this->render('PumukitNewAdminBundle:Person:listrelation'.$template.'.html.twig', 
                             array(
                                   'people' => $multimediaObject->getPeopleInMultimediaObjectByRole($role),
                                   'role' => $role,
                                   'mm' => $multimediaObject
                                   ));
    }

    /**
     * Delete Person
     *
     * @Template("PumukitNewAdminBundle:Person:list.html")
     */
    public function deleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        try{
            if (0 === $personService->countMultimediaObjectsWithPerson($person)){
                $personService->deletePerson($person);
            }
        }catch (\Exception $e){
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());          
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
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

        // TODO locale and find or
        if (array_key_exists('other.en', $criteria)){
            if ('' !== $criteria['other.en']){
                $other = explode(' ', $criteria['other.en']);
                $value = implode('|', array_filter($other));
                $new_criteria['post.en'] = new \MongoRegex('/'.$value.'/i');
                //$new_criteria['firm.en'] = new \MongoRegex('/'.$value.'/i');
                //$new_criteria['bio.en'] = new \MongoRegex('/'.$value.'/i');
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
