<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\NewAdminBundle\Form\Type\PersonType;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\RoleInterface;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonController extends AdminController
{
    public static $resourceName = 'person';
    public static $repoName = Person::class;

    /** @var PersonService */
    protected $personService;
    /** @var ValidatorInterface */
    private $validator;
    private $pumukitLdapEnable;
    private $pumukitSchemaPersonalScopeRoleCode;

    public function __construct(
        DocumentManager $documentManager,
        PaginationService $paginationService,
        FactoryService $factoryService,
        GroupService $groupService,
        UserService $userService,
        SessionInterface $session,
        PersonService $personService,
        TranslatorInterface $translator,
        $pumukitLdapEnable,
        $pumukitSchemaPersonalScopeRoleCode
    ) {
        parent::__construct($documentManager, $paginationService, $factoryService, $groupService, $userService, $session, $translator);
        $this->personService = $personService;
        $this->pumukitLdapEnable = $pumukitLdapEnable;
        $this->pumukitSchemaPersonalScopeRoleCode = $pumukitSchemaPersonalScopeRoleCode;
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("@PumukitNewAdmin/Person/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());
        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $countMmPeople = [];
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $this->personService->countMultimediaObjectsWithPerson($person);
        }

        return [
            'people' => $resources,
            'countMmPeople' => $countMmPeople,
        ];
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("@PumukitNewAdmin/Person/create.html.twig")
     */
    public function createAction(Request $request)
    {
        $locale = $request->getLocale();
        $person = new Person();
        $form = $this->createForm(PersonType::class, $person, ['translator' => $this->translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $person = $this->personService->savePerson($person);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            }
            $errors = $this->validator->validate($person);
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
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("@PumukitNewAdmin/Person/update.html.twig")
     */
    public function updateAction(Request $request)
    {
        $person = $this->personService->findPersonById($request->get('id'));

        $locale = $request->getLocale();
        $form = $this->createForm(PersonType::class, $person, ['translator' => $this->translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $person = $this->personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }

                return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
            }
            $errors = $this->validator->validate($person);
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
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("@PumukitNewAdmin/Person/show.html.twig")
     */
    public function showAction(Request $request)
    {
        $person = $this->personService->findPersonById($request->get('id'));
        $limit = 5;
        $series = $this->personService->findSeriesWithPerson($person, $limit);

        return [
            'person' => $person,
            'series' => $series,
        ];
    }

    /**
     * @Security("is_granted('ROLE_ACCESS_PEOPLE')")
     * @Template("@PumukitNewAdmin/Person/list.html.twig")
     */
    public function listAction(Request $request)
    {
        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());

        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $countMmPeople = [];
        foreach ($resources as $person) {
            $countMmPeople[$person->getId()] = $this->personService->countMultimediaObjectsWithPerson($person);
        }

        return [
            'people' => $resources,
            'countMmPeople' => $countMmPeople,
        ];
    }

    /**
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     * @Template("@PumukitNewAdmin/Person/listautocomplete.html.twig")
     */
    public function listAutocompleteAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $criteria = $this->getCriteria($request->get('criteria', []), $request->getLocale());
        $selectedPersonId = $request->get('selectedPersonId', null);
        $resources = $this->getResources($request, $criteria, $selectedPersonId);

        $template = $multimediaObject->isPrototype() ? '_template' : '';
        $ldapEnabled = $this->pumukitLdapEnable ?? false;

        $owner = $request->get('owner', false);

        try {
            $personalScopeRole = $this->personService->getPersonalScopeRole();
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     * @Template("@PumukitNewAdmin/Person/createrelation.html.twig")
     */
    public function createRelationAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        $person = new Person();
        $person->setName(preg_replace('/\d+ - /', '', $request->get('name')));

        $locale = $request->getLocale();

        $form = $this->createForm(PersonType::class, $person, ['translator' => $this->translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $multimediaObject = $this->personService->createRelationPerson($person, $role, $multimediaObject);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';
            } else {
                $errors = $this->validator->validate($person);
                $textStatus = '';
                foreach ($errors as $error) {
                    $textStatus .= $error->getPropertyPath().' value '.$error->getInvalidValue().': '.$error->getMessage().'. ';
                }

                return new Response($textStatus, 409);
            }
            if ('owner' === $owner) {
                $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
            } else {
                $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
     * @Template("@PumukitNewAdmin/Person/updaterelation.html.twig")
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     */
    public function updateRelationAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        $person = $this->personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        $locale = $request->getLocale();

        $form = $this->createForm(PersonType::class, $person, ['translator' => $this->translator, 'locale' => $locale]);

        if (($request->isMethod('PUT') || $request->isMethod('POST'))) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $person = $this->personService->updatePerson($person);
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                }

                $template = $multimediaObject->isPrototype() ? '_template' : '';

                if ('owner' === $owner) {
                    $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
                } else {
                    $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
            $errors = $this->validator->validate($person);
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     */
    public function linkAction(Request $request, MultimediaObject $multimediaObject, Role $role): Response
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $person = $this->personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        try {
            $multimediaObject = $this->personService->createRelationPerson($person, $role, $multimediaObject);
        } catch (\Exception $e) {
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
        } else {
            $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     */
    public function autoCompleteAction(Request $request, MultimediaObject $multimediaObject, Role $role): JsonResponse
    {
        $name = $request->get('term');

        $excludedPeople = $multimediaObject->getPeopleByRole($role, true);
        $excludedPeopleIds = [];
        foreach ($excludedPeople as $person) {
            $excludedPeopleIds[] = new ObjectId($person->getId());
        }
        $people = $this->personService->autoCompletePeopleByName($name, $excludedPeopleIds, true);

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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role",  options={"id" = "roleId"})
     */
    public function upAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $person = $this->personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();
        $multimediaObject = $this->personService->upPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
        } else {
            $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     */
    public function downAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_ADD_OWNER');
        }

        $person = $this->personService->findPersonById($request->get('id'));
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();
        $multimediaObject = $this->personService->downPersonWithRole($person, $role, $multimediaObject);

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        $owner = $request->get('owner', false);
        if ('owner' === $owner) {
            $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
        } else {
            $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
     * @ParamConverter("multimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", options={"id" = "roleId"})
     */
    public function deleteRelationAction(Request $request, MultimediaObject $multimediaObject, Role $role)
    {
        $person = $this->personService->findPersonById($request->get('id'));

        if ($role->getCod() === $this->pumukitSchemaPersonalScopeRoleCode) {
            $this->denyAccessUnlessGranted('ROLE_MODIFY_OWNER');
        }
        $owner = $request->get('owner', false);

        try {
            $person = $this->personService->findPersonById($request->get('id'));
            $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();
            $multimediaObject = $this->personService->deleteRelation($person, $role, $multimediaObject);
        } catch (\Exception $e) {
            return new Response($this->translator->trans("Can not delete relation of Person '").$person->getName().$this->translator->trans("' with MultimediaObject '").$multimediaObject->getId()."'. ", 409);
        }

        $template = '';
        if (MultimediaObject::STATUS_PROTOTYPE === $multimediaObject->getStatus()) {
            $template = '_template';
        }
        if ('owner' === $owner) {
            $twigTemplate = '@PumukitNewAdmin/MultimediaObject/listownerrelation.html.twig';
        } else {
            $twigTemplate = '@PumukitNewAdmin/Person/listrelation.html.twig';
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
     * @Security("is_granted('ROLE_SCOPE_GLOBAL')")
     * @Template("@PumukitNewAdmin/Person/list.html.twig")
     */
    public function deleteAction(Request $request)
    {
        $person = $this->personService->findPersonById($request->get('id'));

        try {
            if (0 === $this->personService->countMultimediaObjectsWithPerson($person)) {
                $this->personService->deletePerson($person);
            } else {
                return new Response($this->translator->trans("Can't delete Person'").' '.$person->getName().$this->translator->trans("'. There are Multimedia objects with this Person."), 409);
            }
        } catch (\Exception $e) {
            return new Response($this->translator->trans("Can't delete Person'").' '.$person->getName()."'. ", 409);
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
    }

    /**
     * @Security("is_granted('ROLE_SCOPE_GLOBAL')")
     */
    public function batchDeleteAction(Request $request)
    {
        $ids = $request->get('ids');

        if ('string' === gettype($ids)) {
            $ids = json_decode($ids, true);
        }

        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);

        foreach ($ids as $id) {
            $person = $this->find($id);
            if (0 !== count($mmRepo->findByPersonId($person->getId()))) {
                return new Response($this->translator->trans("Can not delete Person '").$person->getName()."'. ", Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($ids as $id) {
            $person = $this->find($id);

            try {
                $this->personService->deletePerson($person);
            } catch (\Exception $e) {
                return new Response($this->translator->trans("Can not delete Person '").$person->getName()."'. ", Response::HTTP_BAD_REQUEST);
            }
            if ($id === $this->session->get('admin/person/id')) {
                $this->session->remove('admin/person/id');
            }
        }

        return $this->redirect($this->generateUrl('pumukitnewadmin_person_list'));
    }

    public function getCriteria($criteria, $locale = 'en')
    {
        if (array_key_exists('reset', $criteria)) {
            $this->session->remove('admin/person/criteria');
        } elseif ($criteria) {
            $this->session->set('admin/person/criteria', $criteria);
        }
        $criteria = $this->session->get('admin/person/criteria', []);

        $new_criteria = [];

        if (array_key_exists('name', $criteria) && array_key_exists('letter', $criteria)) {
            if (('' !== $criteria['name']) && ('' !== $criteria['letter'])) {
                $more = strtoupper($criteria['name'][0]) == strtoupper($criteria['letter']) ? '|^'.$criteria['name'].'.*' : '';
                $new_criteria['name'] = new Regex('^'.$criteria['letter'].'.*'.$criteria['name'].'.*'.$more, 'i');
            } elseif ('' !== $criteria['name']) {
                $new_criteria['name'] = new Regex($criteria['name'], 'i');
            } elseif ('' !== $criteria['letter']) {
                $new_criteria['name'] = new Regex('^'.$criteria['letter'], 'i');
            }
        } elseif (array_key_exists('name', $criteria)) {
            if ('' !== $criteria['name']) {
                $new_criteria['name'] = new Regex($criteria['name'], 'i');
            }
        } elseif (array_key_exists('letter', $criteria)) {
            if ('' !== $criteria['letter']) {
                $new_criteria['name'] = new Regex('^'.$criteria['letter'], 'i');
            }
        }

        if (array_key_exists('post', $criteria)) {
            if ('' !== $criteria['post']) {
                $new_criteria['post.'.$locale] = new Regex($criteria['post'], 'i');
            }
        }

        return $new_criteria;
    }

    public function getSorting(Request $request = null, $session_namespace = null): array
    {
        $session = $this->session;

        if ($sorting = $request->get('sorting')) {
            $session->set('admin/person/type', $sorting[key($sorting)]);
            $session->set('admin/person/sort', key($sorting));
        }

        $value = $session->get('admin/person/type', 'asc');
        $key = $session->get('admin/person/sort', 'name');

        return [$key => $value];
    }

    public function getResources(Request $request, $criteria, $selectedPersonId = null)
    {
        $sorting = $this->getSorting($request);
        $session = $this->session;

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
