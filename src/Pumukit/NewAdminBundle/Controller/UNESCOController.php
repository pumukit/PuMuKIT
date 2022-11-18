<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\NewAdminBundle\Services\MultimediaObjectSearchService;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\SchemaBundle\Services\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/unesco")
 *
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UNESCOController extends AbstractController implements NewAdminControllerInterface
{
    public static $baseTags = [
        'Health Sciences' => [
            'U310000',
            'U240000',
            'U320000',
            'U610000',
        ],
        'Technology' => [
            'U330000',
        ],
        'Sciences' => [
            'U210000',
            'U250000',
            'U220000',
            'U120000',
            'U230000',
        ],
        'Legal' => [
            'U530000',
            'U560000',
            'U590000',
            'U520000',
            'U580000',
            'U630000',
        ],
        'Humanities' => [
            'U510000',
            'U620000',
            'U710000',
            'U720000',
            'U540000',
            'U550000',
            'U570000',
            'U110000',
        ],
    ];

    /** @var PaginationService */
    private $paginationService;
    /** @var TagCatalogueService */
    private $tagCatalogueService;
    /** @var PersonService */
    private $personService;
    /** @var FactoryService */
    private $factoryService;
    /** @var JobService */
    private $jobService;
    /** @var ProfileService */
    private $profileService;
    /** @var TagService */
    private $tagService;
    /** @var DocumentManager */
    private $documentManager;
    /** @var TranslatorInterface */
    private $translator;
    /** @var UserService */
    private $userService;
    /** @var GroupService */
    private $groupService;
    /** @var SessionInterface */
    private $session;
    /** @var RequestStack */
    private $requestStack;
    /** @var RouterInterface */
    private $router;
    /** @var MultimediaObjectSearchService */
    private $multimediaObjectSearchService;
    private $showLatestWithPudeNew;
    private $pumukitNewAdminBaseCatalogueTag;
    private $kernelBundles;

    public function __construct(
        PaginationService $paginationService,
        TagCatalogueService $tagCatalogueService,
        PersonService $personService,
        FactoryService $factoryService,
        JobService $jobService,
        ProfileService $profileService,
        TagService $tagService,
        DocumentManager $documentManager,
        TranslatorInterface $translator,
        UserService $userService,
        GroupService $groupService,
        SessionInterface $session,
        RequestStack $requestStack,
        RouterInterface $router,
        MultimediaObjectSearchService $multimediaObjectSearchService,
        $showLatestWithPudeNew,
        $pumukitNewAdminBaseCatalogueTag,
        $kernelBundles
    ) {
        $this->paginationService = $paginationService;
        $this->tagCatalogueService = $tagCatalogueService;
        $this->personService = $personService;
        $this->factoryService = $factoryService;
        $this->jobService = $jobService;
        $this->profileService = $profileService;
        $this->tagService = $tagService;
        $this->documentManager = $documentManager;
        $this->translator = $translator;
        $this->userService = $userService;
        $this->groupService = $groupService;
        $this->session = $session;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->showLatestWithPudeNew = $showLatestWithPudeNew;
        $this->pumukitNewAdminBaseCatalogueTag = $pumukitNewAdminBaseCatalogueTag;
        $this->kernelBundles = $kernelBundles;
        $this->multimediaObjectSearchService = $multimediaObjectSearchService;
    }

    /**
     * @Route("/", name="pumukitnewadmin_unesco_index")
     * @Template("@PumukitNewAdmin/UNESCO/index.html.twig")
     */
    public function indexAction(Request $request)
    {
        $configuredTag = $this->getConfiguredTag();

        $session = $this->session;
        $page = (int) $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        $paginate = $request->query->get('paginate');
        $session->set('admin/unesco/page', $page);
        if (isset($paginate)) {
            $session->set('admin/unesco/paginate', $paginate);
        }

        return ['configuredTag' => $configuredTag];
    }

    /**
     * @Route("/tags", name="pumukitnewadmin_unesco_menu_tags")
     * @Template("@PumukitNewAdmin/UNESCO/menuTags.html.twig")
     */
    public function menuTagsAction()
    {
        $configuredTag = $this->getConfiguredTag();

        if (null !== $this->pumukitNewAdminBaseCatalogueTag) {
            $menuTags = [];
            foreach ($configuredTag->getChildren() as $child) {
                if ($child->getDisplay()) {
                    $children = $child->getChildren();
                    if (count($children) > 0) {
                        foreach ($children as $elem) {
                            if ($elem->getDisplay()) {
                                $menuTags[$child->getTitle()][] = $elem;
                            }
                        }
                    } else {
                        $menuTags[$configuredTag->getTitle()][] = $child;
                    }
                }
            }
        } else {
            $menuTags = [];

            foreach (static::$baseTags as $key => $tag) {
                foreach ($tag as $cod) {
                    $menuTags[$this->translator->trans($key)][] = $this->documentManager->getRepository(Tag::class)->findOneBy(
                        ['cod' => $cod]
                    );
                }
            }
        }

        $countMultimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->count();

        $countMultimediaObjectsWithoutTag = $this->documentManager->getRepository(MultimediaObject::class)->countWithoutTag(
            $configuredTag
        );
        $defaultTagOptions = [
            [
                'key' => 2,
                'title' => $this->translator->trans('All'),
                'count' => $countMultimediaObjects,
            ],
            [
                'key' => 1,
                'title' => $this->translator->trans('Without category'),
                'count' => $countMultimediaObjectsWithoutTag,
            ],
        ];

        return ['tags' => $menuTags, 'defaultTagOptions' => $defaultTagOptions];
    }

    /**
     * @Route("/list/{tag}", name="pumukitnewadmin_unesco_list")
     * @Template("@PumukitNewAdmin/UNESCO/list.html.twig")
     *
     * @param mixed|null $tag
     */
    public function listAction($tag = null)
    {
        $session = $this->session;
        $page = $session->get('admin/unesco/page', 1);
        $maxPerPage = $session->get('admin/unesco/paginate', 10);

        if (isset($tag) || $session->has('admin/unesco/tag')) {
            $tag = ($tag ?? $session->get('admin/unesco/tag'));
        }
        if ($session->has('UNESCO/criteria')) {
            $multimediaObjects = $this->searchMultimediaObjects($session->get('UNESCO/criteria'), $tag);
        } elseif ($tag) {
            $multimediaObjects = $this->searchMultimediaObjects($session->get('UNESCO/criteria'), $tag);
        } else {
            $multimediaObjects = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
        }

        if ($session->has('admin/unesco/element_sort')) {
            $element_sort = $session->get('admin/unesco/element_sort');
            $sortType = $session->get('admin/unesco/type');

            if ('score' == $sortType) {
                $multimediaObjects->sortMeta('score', 'textScore');
            } else {
                $multimediaObjects->sort($element_sort, $sortType);
            }
        } else {
            if ($session->get('admin/unesco/text', false)) {
                $multimediaObjects->sortMeta('score', 'textScore');
                $session->set('admin/unesco/type', 'score');
            } else {
                $multimediaObjects->sort('public_date', 'desc');
                $session->set('admin/unesco/type', 'desc');
                $session->set('admin/unesco/element_sort', 'public_date');
            }
        }

        $pager = $this->paginationService->createDoctrineODMMongoDBAdapter($multimediaObjects, $page, $maxPerPage);

        if ($pager->getNbPages() < $page) {
            $page = $pager->getNbPages();
            $session->set('admin/unesco/page', $page);
        }

        if ($pager->getNbResults() > 0) {
            $resetCache = true;
            foreach ($pager->getCurrentPageResults() as $result) {
                if ($session->get('admin/unesco/id') == $result->getId()) {
                    $resetCache = false;

                    break;
                }
            }
            if ($resetCache) {
                foreach ($pager->getCurrentPageResults() as $result) {
                    $session->set('admin/unesco/id', $result->getId());

                    break;
                }
            }
        } else {
            $session->remove('admin/unesco/id');
        }

        return [
            'mms' => $pager,
            'disable_pudenew' => !$this->showLatestWithPudeNew,
        ];
    }

    /**
     * @Route("/remove/session/{all}", name="pumukitnewadmin_unesco_removesession")
     *
     * @param mixed $all
     */
    public function resetSessionAction($all = true)
    {
        $session = $this->session;

        $this->tagCatalogueService->resetSessionCriteria($session, $all);

        return new JsonResponse(
            ['success']
        );
    }

    /**
     * @Route("/add/criteria", name="pumukitnewadmin_unesco_addcriteria")
     */
    public function addCriteriaSessionAction(Request $request)
    {
        $session = $this->session;

        $this->tagCatalogueService->addSessionCriteria($request, $session);

        return new JsonResponse(['success']);
    }

    /**
     * @Route("edit/{id}", name="pumukit_new_admin_unesco_edit")
     * @ParamConverter("multimediaObject", options={"mapping": {"id":"id"}})
     * @Template("@PumukitNewAdmin/UNESCO/edit.html.twig")
     */
    public function editUNESCOAction(Request $request, MultimediaObject $multimediaObject)
    {
        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $this->personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $this->personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $this->factoryService->getParentTags();

        $locale = $request->getLocale();
        $formMeta = $this->createForm(MultimediaObjectMetaType::class, $multimediaObject, ['translator' => $this->translator, 'locale' => $locale]);
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $this->translator,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $multimediaObject, $options);

        $session = $this->session;
        $session->set('admin/unesco/id', $multimediaObject->getId());

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->showLatestWithPudeNew) {
            $this->documentManager->getRepository(Tag::class)->findOneByCod('PUDENEW')->setDisplay(false);
        }
        $pubChannelsTags = $this->factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $this->factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->jobService->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->profileService->getProfiles(null, true, false);

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        $activeEditor = $this->checkHasEditor();
        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $opencastExists = array_key_exists('PumukitOpencastBundle', $this->kernelBundles);

        $allGroups = $this->getAllGroups();

        return [
            'mm' => $multimediaObject,
            'form_meta' => $formMeta->createView(),
            'form_pub' => $formPub->createView(),
            'roles' => $roles,
            'personal_scope_role' => $personalScopeRole,
            'personal_scope_role_code' => $personalScopeRoleCode,
            'pub_channels' => $pubChannelsTags,
            'pub_decisions' => $pubDecisionsTags,
            'parent_tags' => $parentTags,
            'jobs' => $jobs,
            'not_master_profiles' => $notMasterProfiles,
            'template' => $template,
            'active_editor' => $activeEditor,
            'opencast_exists' => $opencastExists,
            'not_change_pub_channel' => $notChangePubChannel,
            'groups' => $allGroups,
        ];
    }

    /**
     * @Route("/advance/search/show/{id}", name="pumukitnewadmin_unesco_show")
     * @Template("@PumukitNewAdmin/UNESCO/show.html.twig")
     *
     * @param mixed|null $id
     */
    public function showAction($id = null)
    {
        $activeEditor = $this->checkHasEditor();

        if (isset($id)) {
            $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(
                ['_id' => new ObjectId($id)]
            );
        } else {
            $multimediaObject = null;
        }

        $configuredTag = $this->getConfiguredTag();

        return [
            'configuredTag' => $configuredTag,
            'mm' => $multimediaObject,
            'active_editor' => $activeEditor,
        ];
    }

    /**
     * @Route("/advance/search/form", name="pumukitnewadmin_unesco_advance_search_form")
     * @Template("@PumukitNewAdmin/UNESCO/search_view.html.twig")
     */
    public function advancedSearchFormAction(Request $request)
    {
        $locale = $request->getLocale();

        $roles = $this->documentManager->getRepository(Role::class)->findAll();

        $pudeRadio = $this->documentManager->getRepository(Tag::class)->findOneByCod('PUDERADIO');
        $pudeTV = $this->documentManager->getRepository(Tag::class)->findOneByCod('PUDETV');

        $statusPub = [
            MultimediaObject::STATUS_PUBLISHED => $this->translator->trans('Published'),
            MultimediaObject::STATUS_BLOCKED => $this->translator->trans('Blocked'),
            MultimediaObject::STATUS_HIDDEN => $this->translator->trans('Hidden'),
        ];

        $broadcasts = [
            EmbeddedBroadcast::TYPE_PUBLIC => $this->translator->trans('Public'),
            EmbeddedBroadcast::TYPE_LOGIN => $this->translator->trans('Login'),
            EmbeddedBroadcast::TYPE_PASSWORD => $this->translator->trans('Password'),
            EmbeddedBroadcast::TYPE_GROUPS => $this->translator->trans('Groups'),
        ];

        $type = [
            MultimediaObject::TYPE_VIDEO => $this->translator->trans('Video'),
            MultimediaObject::TYPE_AUDIO => $this->translator->trans('Audio'),
            MultimediaObject::TYPE_EXTERNAL => $this->translator->trans('External player'),
        ];

        $genreParent = $this->documentManager->getRepository(Tag::class)->findOneByCod('GENRE');
        if ($genreParent) {
            $genres = $this->documentManager->getRepository(Tag::class)->findBy(['parent.$id' => new ObjectId($genreParent->getId())]);
            $aGenre = [];
            foreach ($genres as $genre) {
                $aGenre[$genre->getCod()] = $genre->getTitle($locale);
            }
        } else {
            $aGenre = [];
        }

        $groups = $this->getAllGroups();

        return [
            'disable_pudenew' => !$this->showLatestWithPudeNew,
            'groups' => $groups,
            'genre' => $aGenre,
            'roles' => $roles,
            'statusPub' => $statusPub,
            'broadcasts' => $broadcasts,
            'years' => $this->getMmobjsYears(),
            'type' => $type,
            'puderadio' => $pudeRadio,
            'pudetv' => $pudeTV,
        ];
    }

    /**
     * @Route("/delete/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_delete_tag")
     */
    public function deleteTagDnDAction(string $tagCod, string $multimediaObjectId)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);

        $tag = $this->documentManager->getRepository(Tag::class)->findOneByCod($tagCod);
        $tagConfigured = $this->getConfiguredTag();
        $removedTags = [];

        if ($tag->isDescendantOf($tagConfigured)) {
            $removedTags = $this->tagService->removeTagFromMultimediaObject($multimediaObject, $tag->getId());
        }

        if (empty($removedTags)) {
            return new JsonResponse(['error' => $this->translator->trans("Can't delete this tag, delete first children"), JsonResponse::HTTP_BAD_REQUEST]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @Route("/add/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_add_tag")
     */
    public function addTagDnDAction(string $tagCod, string $multimediaObjectId)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);

        $tag = $this->documentManager->getRepository(Tag::class)->findOneByCod($tagCod);
        if ($multimediaObject->containsTag($tag)) {
            return new JsonResponse(['error' => JsonResponse::HTTP_BAD_REQUEST]);
        }

        $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        return new JsonResponse(['success']);
    }

    /**
     * @Route("/option/selected/{option}", name="pumukitnewadmin_unesco_options_list")
     *
     * @param mixed $option
     */
    public function optionsMultimediaObjectsAction(Request $request, $option)
    {
        $session = $this->session;
        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');

        $data = $request->request->get('data');

        switch ($option) {
        case 'delete_selected':
            foreach ($data as $multimediaObjectId) {
                $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);
                $this->factoryService->deleteMultimediaObject($multimediaObject);
            }

            break;

        case 'invert_announce_selected':
            $pudeNew = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUDENEW']);
            foreach ($data as $multimediaObjectId) {
                $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);
                if ($multimediaObject->containsTag($pudeNew)) {
                    $this->tagService->removeTagFromMultimediaObject($multimediaObject, $pudeNew->getId());
                } else {
                    $this->tagService->addTagToMultimediaObject($multimediaObject, $pudeNew->getId());
                }
            }

            break;

        default:
            break;
        }

        return new JsonResponse(['success']);
    }

    /**
     * @Route("/delete/mms/{multimediaObjectId}", name="pumukitnewadmin_unesco_delete")
     */
    public function deleteAction(string $multimediaObjectId)
    {
        $session = $this->session;
        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');

        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);

        try {
            $this->factoryService->deleteMultimediaObject($multimediaObject);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $this->translator->trans("Can't delete this multimediaObject")]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @Route("/clone/mms/{multimediaObjectId}", name="pumukitnewadmin_unesco_clone")
     */
    public function cloneAction(string $multimediaObjectId)
    {
        $multimediaObject = $this->documentManager->getRepository(MultimediaObject::class)->findOneBy(['_id' => new ObjectId($multimediaObjectId)]);

        try {
            $this->factoryService->cloneMultimediaObject($multimediaObject);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $this->translator->trans("Can't clone this multimediaObject")]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @Template("@PumukitNewAdmin/UNESCO/custom_fields.html.twig")
     */
    public function customFieldsAction(Request $request)
    {
        $session = $this->session;

        if (!$session->has('admin/unesco/selected_fields')) {
            $defaultSelectedFields = $this->tagCatalogueService->getDefaultListFields();
            $session->set('admin/unesco/selected_fields', $defaultSelectedFields);
        }

        return [
            'selectedFields' => $session->get('admin/unesco/selected_fields'),
        ];
    }

    /**
     * @Route("/custom/fields/add", name="pumukitnewadmin_catalogue_custom_fields")
     */
    public function setCustomFields(Request $request)
    {
        $customFields = array_filter($request->request->all(), function ($value) {
            return -1 != $value;
        });

        if (!$customFields) {
            return new JsonResponse(['success']);
        }

        if ($request->getSession()->has('admin/unesco/selected_fields')) {
            $selectedFields = $request->getSession()->get('admin/unesco/selected_fields');
            $selectedFields = array_merge($selectedFields, $customFields);
            $request->getSession()->set('admin/unesco/selected_fields', $selectedFields);
        }

        return new JsonResponse(['success']);
    }

    private function searchMultimediaObjects($criteria, $tag = null)
    {
        $configuredTag = $this->getConfiguredTag();

        $session = $this->session;
        $session->set('admin/unesco/tag', $tag);

        $tagCondition = $tag;
        if (isset($tag) && !in_array($tag, ['1', '2'])) {
            $tagCondition = 'tag';
        }

        switch ($tagCondition) {
            case '1':
                // NOTE: Videos without configured tag

                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $configuredTag->getCod()]);
                $query = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
                    ->field('tags.cod')
                    ->notEqual($selectedTag->getCod())
                ;

                break;

            case 'tag':
                $selectedTag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $tag]);
                $query = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
                    ->field('tags.cod')
                    ->equals($selectedTag->getCod())
                ;

                break;

            case '2':
            default:
                // NOTE: All videos
                $query = $this->documentManager->getRepository(MultimediaObject::class)->createStandardQueryBuilder();

                break;
        }

        if (isset($criteria) && !empty($criteria)) {
            $query = $this->addCriteria($query, $criteria);
        }

        return $query;
    }

    private function addCriteria($query, $criteria)
    {
        $request = $this->requestStack->getMasterRequest();

        foreach ($criteria as $key => $field) {
            if ('roles' === $key && count($field) >= 1) {
                foreach ($field as $key2 => $value) {
                    $query->field('people')->elemMatch($query->expr()->field('cod')->equals($key2)->field('people.name')->equals($value));
                }
            } elseif ('public_date_init' === $key && !empty($field)) {
                $public_date_init = $field;
            } elseif ('public_date_finish' === $key && !empty($field)) {
                $public_date_finish = $field;
            } elseif ('record_date_init' === $key && !empty($field)) {
                $record_date_init = $field;
            } elseif ('record_date_finish' === $key && !empty($field)) {
                $record_date_finish = $field;
            } elseif ('$text' === $key && !empty($field)) {
                if (preg_match('/^[0-9a-z]{24}$/', $field)) {
                    $query->field('_id')->equals($field);
                } else {
                    $this->multimediaObjectSearchService->completeSearchQueryBuilder(
                        $field,
                        $query,
                        $request->getLocale()
                    );
                }
            } elseif ('type' === $key && !empty($field)) {
                if ('all' !== $field) {
                    $query->field('type')->equals($field);
                }
            } elseif ('tracks.duration' == $key && !empty($field)) {
                $query = $this->findDuration($query, $key, $field);
            } elseif ('year' === $key && !empty($field)) {
                $query = $this->findDuration($query, 'year', $field);
            } else {
                $query->field($key)->equals($field);
            }
        }

        if (isset($public_date_init, $public_date_finish)) {
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_init) * 1000),
                new UTCDateTime(strtotime($public_date_finish) * 1000)
            );
        } elseif (isset($public_date_init)) {
            $date = date($public_date_init.'T23:59:59');
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_init) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        } elseif (isset($public_date_finish)) {
            $date = date($public_date_finish.'T23:59:59');
            $query->field('public_date')->range(
                new UTCDateTime(strtotime($public_date_finish) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        }

        if (isset($record_date_init, $record_date_finish)) {
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_init) * 1000),
                new UTCDateTime(strtotime($record_date_finish) * 1000)
            );
        } elseif (isset($record_date_init)) {
            $date = date($record_date_init.'T23:59:59');
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_init) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        } elseif (isset($record_date_finish)) {
            $date = date($record_date_finish.'T23:59:59');
            $query->field('record_date')->range(
                new UTCDateTime(strtotime($record_date_finish) * 1000),
                new UTCDateTime(strtotime($date) * 1000)
            );
        }

        return $query;
    }

    private function getMmobjsYears()
    {
        $mmObjColl = $this->documentManager->getDocumentCollection(
            MultimediaObject::class
        );
        $pipeline = [
            ['$match' => ['status' => MultimediaObject::STATUS_PUBLISHED]],
            ['$group' => ['_id' => ['$year' => '$record_date']]],
            ['$sort' => ['_id' => 1]],
        ];
        $yearResults = $mmObjColl->aggregate($pipeline, ['cursor' => []]);
        $years = [];
        foreach ($yearResults as $year) {
            $years[] = $year['_id'];
        }

        return $years;
    }

    private function findDuration($query, $key, $field)
    {
        if ('tracks.duration' === $key) {
            if ('-5' == $field) {
                $query->field($key)->lte(300);
            }
            if ('-10' == $field) {
                $query->field($key)->lte(600);
            }
            if ('-30' == $field) {
                $query->field($key)->lte(1800);
            }
            if ('-60' == $field) {
                $query->field($key)->lte(3600);
            }
            if ('+60' == $field) {
                $query->field($key)->gt(3600);
            }
        } elseif ('year' === $key) {
            $start = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', $field));
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', ((int) $field) + 1));
            $query->field('record_date')->gte($start);
            $query->field('record_date')->lt($end);
        }

        return $query;
    }

    private function checkHasEditor()
    {
        $routes = $this->router->getRouteCollection()->all();

        return array_key_exists('pumukit_videoeditor_index', $routes);
    }

    private function getAllGroups()
    {
        $loggedInUser = $this->getUser();
        if ($loggedInUser->isSuperAdmin() || $this->userService->hasGlobalScope($loggedInUser)) {
            $allGroups = $this->groupService->findAll();
        } else {
            $allGroups = $loggedInUser->getGroups();
        }

        return $allGroups;
    }

    private function getConfiguredTag()
    {
        return $this->tagCatalogueService->getConfiguredTag();
    }
}
