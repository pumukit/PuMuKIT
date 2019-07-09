<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pumukit\NewAdminBundle\Form\Type\PersonType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PersonController extends AdminController implements NewAdminControllerInterface
{
    public static $resourceName = 'person';
    public static $repoName = Person::class;

    /**
     * Index.
     *
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("PumukitNewAdminBundle:Person:index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());
        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = [];
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return [
            'people' => $resources,
            'countMmPeople' => $countMmPeople,
        ];
    }

    /**
     * Create new person.
     *
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("PumukitNewAdminBundle:Person:create.html.twig")
     */
    public function createAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $person = new Person();
        $form = $this->createForm(PersonType::class, $person, ['translator' => $translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $person = $personService->savePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            }
            $errors = $this->get('validator')->validate($person);
            $textStatus = '';
            foreach ($errors as $error) {
                $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
            }

            return new Response($textStatus, 409);
        }

        return [
            'person' => $person,
            'form' => $form->createView(),
        ];
    }

    /**
     * Update person.
     *
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("PumukitNewAdminBundle:Person:update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(PersonType::class, $person, ['translator' => $translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $person = $personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            }
            $errors = $this->get('validator')->validate($person);
            $textStatus = '';
            foreach ($errors as $error) {
                $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
            }

            return new Response($textStatus, 409);
        }

        return [
            'person' => $person,
            'form' => $form->createView(),
        ];
    }

    /**
     * Show person.
     *
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("PumukitNewAdminBundle:Person:show.html.twig")
     */
    public function showAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $limit = 5;
        $series = $personService->findSeriesWithPerson($person, $limit);

        return [
            'person' => $person,
            'series' => $series,
        ];
    }

    /**
     * List people.
     *
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("PumukitNewAdminBundle:Person:list.html.twig")
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());

        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $personService = $this->get('pumukitschema.person');
        $countMmPeople = [];
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $personService->countMultimediaObjectsWithPerson($person);
        }

        return [
            'people' => $resources,
            'countMmPeople' => $countMmPeople,
        ];
    }

    /**
     * Create new person with role from Multimedia Object.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:listautocomplete.html.twig")
     */
    public function listAutocompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());
        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $template = $multimediaObject->isPrototype() ? '_template' : '';
        $ldapEnabled = $this->container->has('pumukit_ldap.ldap');

        $owner = $request->get('owner', false);
        $personService = $this->get('pumukitschema.person');

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        return [
            'people' => $resources,
            'mm' => $multimediaObject,
            'role' => $role,
            'template' => $template,
            'ldap_enabled' => $ldapEnabled,
            'owner' => $owner,
            'personal_scope_role_code' => $personalScopeRole->getCod(),
        ];
    }

    /**
     * Create relation.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitNewAdminBundle:Person:createrelation.html.twig")
     */
    public function createRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(PersonType::class, $person, ['translator' => $translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $personService = $this->get('pumukitschema.person');
            $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';
            } else {
                $errors = $this->get('validator')->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
            if ('owner' === $owner) {
                $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
            } else {
                $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
            }

            return $this->render(
                $twigTemplate,
                [
                    'people' => $multimediaObject->getPeopleByRole($role, true),
                    'role' => $role,
                    'personal_scope_role_code' => $personalScopeRoleCode,
                    'mm' => $multimediaObject,
                    'template' => $template,
                ]
            );
        }

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return [
            'person' => $person,
            'role' => $role,
            'mm' => $multimediaObject,
            'template' => $template,
            'form' => $form->createView(),
            'owner' => $owner,
        ];
    }

    /**
     * Update relation.
     *
     * @Template("PumukitNewAdminBundle:Person:updaterelation.html.twig")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function updateRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(PersonType::class, $person, ['translator' => $translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                try {
                    $person = $personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';

                if ('owner' === $owner) {
                    $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
                } else {
                    $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
                }

                return $this->render(
                    $twigTemplate,
                    [
                        'people' => $multimediaObject->getPeopleByRole($role, true),
                        'role' => $role,
                        'personal_scope_role_code' => $personalScopeRoleCode,
                        'mm' => $multimediaObject,
                        'template' => $template,
                    ]
                );
            }
            $errors = $this->get('validator')->validate($person);
            $textStatus = '';
            foreach ($errors as $error) {
                $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
            }

            return new Response($textStatus, 409);
        }

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return [
            'person' => $person,
            'role' => $role,
            'mm' => $multimediaObject,
            'template' => $template,
            'form' => $form->createView(),
            'owner' => $owner,
        ];
    }

    /**
     * Link person to multimedia object with role.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function linkAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        try {
            $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
        } catch (\Exception $e) {
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
        } else {
            $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
        }

        return $this->render(
            $twigTemplate,
            [
                'people' => $multimediaObject->getPeopleByRole($role, true),
                'role' => $role,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'mm' => $multimediaObject,
                'template' => $template,
            ]
        );
    }

    /**
     * Auto complete.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function autoCompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $name = $request->get('term');

        $excludedPeople = $multimediaObject->getPeopleByRole($role, true);
        $excludedPeopleIds = [];
        foreach ($excludedPeople as $person) {
            $excludedPeopleIds[] = new \MongoId($person->getId());
        }
        $people = $personService->autoCompletePeopleByName($name, $excludedPeopleIds, true);

        $out = [];
        foreach ($people as $p) {
            $out[] = [
                'id' => $p->getId(),
                'label' => $p->getName(),
                'desc' => $p->getPost().' '.$p->getFirm(),
                'value' => $p->getName(),
            ];
        }

        return new JsonResponse($out);
    }

    /**
     * Up person in MultimediaObject.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function upAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
        $multimediaObject = $personService->upPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
        } else {
            $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
        }

        return $this->render(
            $twigTemplate,
            [
                'people' => $multimediaObject->getPeopleByRole($role, true),
                'role' => $role,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'mm' => $multimediaObject,
                'template' => $template,
            ]
        );
    }

    /**
     * Down person in MultimediaObject.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function downAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
        $multimediaObject = $personService->downPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
        } else {
            $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
        }

        return $this->render(
            $twigTemplate,
            [
                'people' => $multimediaObject->getPeopleByRole($role, true),
                'role' => $role,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'mm' => $multimediaObject,
                'template' => $template,
            ]
        );
    }

    /**
     * Delete relation: EmbeddedPerson in Multimedia Object.
     *
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function deleteRelationAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $translator = $this->get('translator');
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));

        if ($role->getCod() === $this->container->getParameter('pumukitschema.personal_scope_role_code')) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        try {
            $person = $personService->findPersonById($request->get('id'));
            $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
            $multimediaObject = $personService->deleteRelation($person, $role, $multimediaObject);
        } catch (\Exception $e) {
            return new Response($translator->trans("Can not delete relation of Person '").$person->getName().$translator->trans("' with MultimediaObject '").$multimediaObject->getId()."'. ", 409);
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        if ('owner' === $owner) {
            $twigTemplate = 'PumukitNewAdminBundle:MultimediaObject:listownerrelation.html.twig';
        } else {
            $twigTemplate = 'PumukitNewAdminBundle:Person:listrelation.html.twig';
        }

        return $this->render(
            $twigTemplate,
            [
                'people' => $multimediaObject->getPeopleByRole($role, true),
                'role' => $role,
                'personal_scope_role_code' => $personalScopeRoleCode,
                'mm' => $multimediaObject,
                'template' => $template,
            ]
        );
    }

    /**
     * Delete Person.
     *
     * @Security("is_granted('ROLE_SCOPE_GLOBAL')")
     * @Template("PumukitNewAdminBundle:Person:list.html.twig")
     */
    public function deleteAction(Request $request)
    {
        $personService = $this->get('pumukitschema.person');
        $person = $personService->findPersonById($request->get('id'));
        $translator = $this->get('translator');

        try {
            if (0 === $personService->countMultimediaObjectsWithPerson($person)) {
                $personService->deletePerson($person);
            } else {
                return new Response($translator->trans("Can't delete Person'").' '.$person->getName().$translator->trans("'. There are Multimedia objects with this Person."), 409);
            }
        } catch (\Exception $e) {
            return new Response($translator->trans("Can't delete Person'").' '.$person->getName()."'. ", 409);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
    }

    /**
     * Batch delete Person
     * Overwrite to use PersonService.
     *
     * @Security("is_granted('ROLE_SCOPE_GLOBAL')")
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $personService = $this->get('pumukitschema.person');
        $translator = $this->get('translator');
        $dm = $this->get('doctrine_mongodb')->getManager();
        $mmRepo = $dm->getRepository(MultimediaObject::class);

        foreach ($ids as $id) {
            $person = $this->find($id);
            if (0 !== count($mmRepo->findByPersonId($person->getId()))) {
                return new Response($translator->trans("Can not delete Person '").$person->getName()."'. ", Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($ids as $id) {
            $person = $this->find($id);

            try {
                $personService->deletePerson($person);
            } catch (\Exception $e) {
                return new Response($translator->trans("Can not delete Person '").$person->getName()."'. ", Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->get('session')->get('admin/person/id')) {
                $this->get('session')->remove('admin/person/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
    }

    /**
     * Gets the criteria values.
     *
     * @param mixed $criteria
     * @param mixed $locale
     */
    public function getCriteria($criteria, $locale = 'en')
    {
        if (array_key_exists('reset', $criteria)) {
            $this->get('session')->remove('admin/person/criteria');
        } elseif ($criteria) {
            $this->get('session')->set('admin/person/criteria', $criteria);
        }
        $criteria = $this->get('session')->get('admin/person/criteria', []);

        $new_criteria = [];

        if (array_key_exists('name', $criteria) && array_key_exists('letter', $criteria)) {
            if (('' !== $criteria['name']) && ('' !== $criteria['letter'])) {
                $more = strtoupper($criteria['name'][0]) == strtoupper($criteria['letter']) ? '|^'.$criteria['name'].'.*' : '';
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'.*'.$criteria['name'].'.*'.$more.'/i');
            } elseif ('' !== $criteria['name']) {
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            } elseif ('' !== $criteria['letter']) {
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        } elseif (array_key_exists('name', $criteria)) {
            if ('' !== $criteria['name']) {
                $new_criteria['name'] = new \MongoRegex('/'.$criteria['name'].'/i');
            }
        } elseif (array_key_exists('letter', $criteria)) {
            if ('' !== $criteria['letter']) {
                $new_criteria['name'] = new \MongoRegex('/^'.$criteria['letter'].'/i');
            }
        }

        if (array_key_exists('post', $criteria)) {
            if ('' !== $criteria['post']) {
                $new_criteria['post.'.$locale] = new \MongoRegex('/'.$criteria['post'].'/i');
            }
        }

        return $new_criteria;
    }

    /**
     * Get sorting for person.
     *
     * @param null|mixed $session_namespace
     */
    public function getSorting(Request $request = null, $session_namespace = null)
    {
        $session = $this->get('session');

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/person/type', $sorting[key($sorting)]);
            $session->set('admin/person/sort', key($sorting));
        }

        $value = $session->get('admin/person/type', 'asc');
        $key = $session->get('admin/person/sort', 'name');

        return [$key => $value];
    }

    /**
     * Gets the list of resources according to a criteria.
     *
     * @param mixed      $criteria
     * @param null|mixed $selectedPersonId
     */
    public function getResources(Request $request, $criteria, $selectedPersonId = null)
    {
        $sorting = $this->getSorting($request);
        $session = $this->get('session');

        $resources = $this->createPager($criteria, $sorting);

        if ($request->get('page', null)) {
            $session->set('admin/person/page', $request->get('page', 1));
        }

        if ($selectedPersonId) {
            $adapter = clone $resources->getAdapter();
            $returnedPerson = $adapter->getSlice(0, $adapter->getNbResults());
            $position = 1;
            foreach ($returnedPerson as $person) {
                if ($selectedPersonId == $person->getId()) {
                    break;
                }
                ++$position;
            }
            $maxPerPage = $session->get('admin/person/paginate', 10);
            $page = (int) (ceil($position / $maxPerPage));
        } else {
            $maxPerPage = $session->get('admin/person/paginate', 10);
            $page = $session->get('admin/person/page', 1);
        }

        $resources
            ->setMaxPerPage($maxPerPage)
            ->setNormalizeOutOfRangePages(true)
            ->setCurrentPage($page)
        ;

        return $resources;
    }
}
