<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectMetaType;
use Pumukit\NewAdminBundle\Form\Type\MultimediaObjectPubType;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Security\Permission;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/unesco")
 *
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class UNESCOController extends Controller implements NewAdminControllerInterface
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

    /**
     * @Route("/", name="pumukitnewadmin_unesco_index")
     * @Template("PumukitNewAdminBundle:UNESCO:index.html.twig")
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $configuredTag = $this->getConfiguredTag();

        $session = $this->get('session');
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
     * @Template("PumukitNewAdminBundle:UNESCO:menuTags.html.twig")
     *
     * @throws \Exception
     *
     * @return array
     */
    public function menuTagsAction()
    {
        $configuredTag = $this->getConfiguredTag();

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        if (null !== $this->container->getParameter('pumukit_new_admin.base_catalogue_tag')) {
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
                    $menuTags[$translator->trans($key)][] = $dm->getRepository(Tag::class)->findOneBy(
                        ['cod' => $cod]
                    );
                }
            }
        }

        $countMultimediaObjects = $dm->getRepository(MultimediaObject::class)->count();

        $countMultimediaObjectsWithoutTag = $dm->getRepository(MultimediaObject::class)->findWithoutTag(
            $configuredTag
        );

        $defaultTagOptions = [
            [
                'key' => 2,
                'title' => $translator->trans('All'),
                'count' => $countMultimediaObjects,
            ],
            [
                'key' => 1,
                'title' => $translator->trans('Without category'),
                'count' => count($countMultimediaObjectsWithoutTag),
            ],
        ];

        return ['tags' => $menuTags, 'defaultTagOptions' => $defaultTagOptions];
    }

    /**
     * @Route("/list/{tag}", name="pumukitnewadmin_unesco_list")
     * @Template("PumukitNewAdminBundle:UNESCO:list.html.twig")
     *
     * @param null $tag
     *
     * @throws \Exception
     *
     * @return array
     */
    public function listAction($tag = null)
    {
        $session = $this->get('session');
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
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $multimediaObjects = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder();
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

        $adapter = new DoctrineODMMongoDBAdapter($multimediaObjects);
        $adapter = new Pagerfanta($adapter);

        $adapter->setMaxPerPage($maxPerPage)->setNormalizeOutOfRangePages(true);

        if ($adapter->getNbPages() < $page) {
            $page = $adapter->getNbPages();
            $session->set('admin/unesco/page', $page);
        }

        $adapter->setCurrentPage($page);

        if ($adapter->getNbResults() > 0) {
            $resetCache = true;
            foreach ($adapter->getCurrentPageResults() as $result) {
                if ($session->get('admin/unesco/id') == $result->getId()) {
                    $resetCache = false;

                    break;
                }
            }
            if ($resetCache) {
                foreach ($adapter->getCurrentPageResults() as $result) {
                    $session->set('admin/unesco/id', $result->getId());

                    break;
                }
            }
        } else {
            $session->remove('admin/unesco/id');
        }

        return [
            'mms' => $adapter,
            'disable_pudenew' => !$this->container->getParameter('show_latest_with_pudenew'),
        ];
    }

    /**
     * @Route("/remove/session/{all}", name="pumukitnewadmin_unesco_removesession")
     *
     * @param bool $all
     *
     * @return JsonResponse
     */
    public function resetSessionAction($all = true)
    {
        $tagCatalogueService = $this->get('pumukitnewadmin.tag_catalogue');
        $session = $this->get('session');

        $tagCatalogueService->resetSessionCriteria($session, $all);

        return new JsonResponse(
            ['success']
        );
    }

    /**
     * @Route("/add/criteria", name="pumukitnewadmin_unesco_addcriteria")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addCriteriaSessionAction(Request $request)
    {
        $tagCatalogueService = $this->get('pumukitnewadmin.tag_catalogue');
        $session = $this->get('session');

        $tagCatalogueService->addSessionCriteria($request, $session);

        return new JsonResponse(['success']);
    }

    /**
     * @param Request          $request
     * @param MultimediaObject $multimediaObject
     *
     * @throws \Exception
     *
     * @return array|Response
     *
     * @Route("edit/{id}", name="pumukit_new_admin_unesco_edit")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"mapping": {"id":
     *                                     "id"}})
     * @Template("PumukitNewAdminBundle:UNESCO:edit.html.twig")
     */
    public function editUNESCOAction(Request $request, MultimediaObject $multimediaObject)
    {
        $factoryService = $this->get('pumukitschema.factory');
        $personService = $this->get('pumukitschema.person');

        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();

        try {
            $personalScopeRole = $personService->getPersonalScopeRole();
        } catch (\Exception $e) {
            return new Response($e, Response::HTTP_BAD_REQUEST);
        }

        $roles = $personService->getRoles();
        if (null === $roles) {
            throw new \Exception('Not found any role.');
        }

        $parentTags = $factoryService->getParentTags();

        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $formMeta = $this->createForm(MultimediaObjectMetaType::class, $multimediaObject, ['translator' => $translator, 'locale' => $locale]);
        $options = [
            'not_granted_change_status' => !$this->isGranted(Permission::CHANGE_MMOBJECT_STATUS),
            'translator' => $translator,
            'locale' => $locale,
        ];
        $formPub = $this->createForm(MultimediaObjectPubType::class, $multimediaObject, $options);

        $session = $this->get('session');
        $session->set('admin/unesco/id', $multimediaObject->getId());

        //If the 'pudenew' tag is not being used, set the display to 'false'.
        if (!$this->container->getParameter('show_latest_with_pudenew')) {
            $dm = $this->container->get('doctrine_mongodb')->getManager();
            $dm->getRepository(Tag::class)->findOneByCod('PUDENEW')->setDisplay(false);
        }
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);
        $pubDecisionsTags = $factoryService->getTagsByCod('PUBDECISIONS', true);

        $jobs = $this->get('pumukitencoder.job')->getNotFinishedJobsByMultimediaObjectId($multimediaObject->getId());

        $notMasterProfiles = $this->get('pumukitencoder.profile')->getProfiles(null, true, false);

        $template = $multimediaObject->isPrototype() ? '_template' : '';

        $activeEditor = $this->checkHasEditor();
        $notChangePubChannel = !$this->isGranted(Permission::CHANGE_MMOBJECT_PUBCHANNEL);
        $allBundles = $this->container->getParameter('kernel.bundles');
        $opencastExists = array_key_exists('PumukitOpencastBundle', $allBundles);

        $allGroups = $this->getAllGroups();

        return [
            'mm' => $multimediaObject,
            'form_meta' => $formMeta->createView(),
            'form_pub' => $formPub->createView(),
            //'series' => $series,
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
     * @Template("PumukitNewAdminBundle:UNESCO:show.html.twig")
     *
     * @param null|string $id
     *
     * @throws \Exception
     *
     * @return array
     * @return array
     */
    public function showAction($id = null)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $activeEditor = $this->checkHasEditor();

        if (isset($id)) {
            $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneBy(
                ['_id' => new \MongoId($id)]
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
     * @param Request $request
     * @Route("/advance/search/form", name="pumukitnewadmin_unesco_advance_search_form")
     * @Template("PumukitNewAdminBundle:UNESCO:search_view.html.twig")
     *
     * @return array
     */
    public function advancedSearchFormAction(Request $request)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $roles = $dm->getRepository(Role::class)->findAll();

        $pudeRadio = $dm->getRepository(Tag::class)->findOneByCod('PUDERADIO');
        $pudeTV = $dm->getRepository(Tag::class)->findOneByCod('PUDETV');

        $statusPub = [
            MultimediaObject::STATUS_PUBLISHED => $translator->trans('Published'),
            MultimediaObject::STATUS_BLOCKED => $translator->trans('Blocked'),
            MultimediaObject::STATUS_HIDDEN => $translator->trans('Hidden'),
        ];

        $broadcasts = [
            EmbeddedBroadcast::TYPE_PUBLIC => $translator->trans('Public'),
            EmbeddedBroadcast::TYPE_LOGIN => $translator->trans('Login'),
            EmbeddedBroadcast::TYPE_PASSWORD => $translator->trans('Password'),
            EmbeddedBroadcast::TYPE_GROUPS => $translator->trans('Groups'),
        ];

        $type = [
            MultimediaObject::TYPE_VIDEO => $translator->trans('Video'),
            MultimediaObject::TYPE_AUDIO => $translator->trans('Audio'),
        ];

        $genreParent = $dm->getRepository(Tag::class)->findOneByCod('GENRE');
        if ($genreParent) {
            $genres = $dm->getRepository(Tag::class)->findBy(['parent.$id' => new \MongoId($genreParent->getId())]);
            $aGenre = [];
            foreach ($genres as $genre) {
                $aGenre[$genre->getCod()] = $genre->getTitle($locale);
            }
        } else {
            $aGenre = [];
        }

        $disablePudenew = !$this->container->getParameter('show_latest_with_pudenew');

        $groups = $this->getAllGroups();

        return [
            //'form' => $form->createView(),
            'disable_pudenew' => $disablePudenew,
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
     * @param $tagCod
     * @param $multimediaObjectId
     *
     * @throws \Exception
     *
     * @return JsonResponse
     *
     * @Route("/delete/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_delete_tag")
     */
    public function deleteTagDnDAction($tagCod, $multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $tagService = $this->container->get('pumukitschema.tag');
        $translator = $this->get('translator');

        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(
            new \MongoId($multimediaObjectId)
        );

        $tag = $dm->getRepository(Tag::class)->findOneByCod($tagCod);
        $tagConfigured = $this->getConfiguredTag();
        $removedTags = [];

        if ($tag->isDescendantOf($tagConfigured)) {
            $removedTags = $tagService->removeTagFromMultimediaObject($multimediaObject, $tag->getId());
        }

        if (empty($removedTags)) {
            return new JsonResponse(['error' => $translator->trans("Can't delete this tag, delete first children"), JsonResponse::HTTP_BAD_REQUEST]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @param $tagCod
     * @param $multimediaObjectId
     *
     * @return JsonResponse
     * @Route("/add/tag/{multimediaObjectId}/{tagCod}", name="pumukitnewadmin_unesco_add_tag")
     */
    public function addTagDnDAction($tagCod, $multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $tagService = $this->container->get('pumukitschema.tag');

        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(
            new \MongoId($multimediaObjectId)
        );

        $tag = $dm->getRepository(Tag::class)->findOneByCod($tagCod);
        if ($multimediaObject->containsTag($tag)) {
            return new JsonResponse(['error' => JsonResponse::HTTP_BAD_REQUEST]);
        }

        $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        return new JsonResponse(['success']);
    }

    /**
     * @param Request $request
     * @param string  $option
     *
     * @return JsonResponse
     * @Route("/option/selected/{option}", name="pumukitnewadmin_unesco_options_list")
     */
    public function optionsMultimediaObjectsAction(Request $request, $option)
    {
        $session = $this->get('session');
        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');

        $data = $request->request->get('data');
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        switch ($option) {
        case 'delete_selected':
            $factoryService = $this->get('pumukitschema.factory');
            foreach ($data as $multimediaObjectId) {
                $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneBy(['_id' => new \MongoId($multimediaObjectId)]);
                $factoryService->deleteMultimediaObject($multimediaObject);
            }

            break;
        case 'invert_announce_selected':
            $tagService = $this->container->get('pumukitschema.tag');
            $pudeNew = $dm->getRepository(Tag::class)->findOneBy(['cod' => 'PUDENEW']);
            foreach ($data as $multimediaObjectId) {
                $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneBy(['_id' => new \MongoId($multimediaObjectId)]);
                if ($multimediaObject->containsTag($pudeNew)) {
                    $tagService->removeTagFromMultimediaObject($multimediaObject, $pudeNew->getId());
                } else {
                    $tagService->addTagToMultimediaObject($multimediaObject, $pudeNew->getId());
                }
            }

            break;
        default:
            break;
        }

        return new JsonResponse(['success']);
    }

    /**
     * @param $multimediaObjectId
     *
     * @return JsonResponse
     * @Route("/delete/mms/{multimediaObjectId}", name="pumukitnewadmin_unesco_delete")
     */
    public function deleteAction($multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $session = $this->get('session');
        $session->remove('admin/unesco/tag');
        $session->remove('admin/unesco/page');
        $session->remove('admin/unesco/paginate');
        $session->remove('admin/unesco/id');
        $translator = $this->get('translator');
        $factoryService = $this->get('pumukitschema.factory');

        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(new \MongoId($multimediaObjectId));

        try {
            $factoryService->deleteMultimediaObject($multimediaObject);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $translator->trans("Can't delete this multimediaObject")]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @param $multimediaObjectId
     *
     * @return JsonResponse
     * @Route("/clone/mms/{multimediaObjectId}", name="pumukitnewadmin_unesco_clone")
     */
    public function cloneAction($multimediaObjectId)
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $factoryService = $this->get('pumukitschema.factory');

        $multimediaObject = $dm->getRepository(MultimediaObject::class)->findOneById(new \MongoId($multimediaObjectId));

        try {
            $factoryService->cloneMultimediaObject($multimediaObject);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $translator->trans("Can't clone this multimediaObject")]);
        }

        return new JsonResponse(['success']);
    }

    /**
     * @Template("PumukitNewAdminBundle:UNESCO:custom_fields.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function customFieldsAction(Request $request)
    {
        $session = $this->get('session');
        $tagCatalogueService = $this->get('pumukitnewadmin.tag_catalogue');

        if (!$session->has('admin/unesco/selected_fields')) {
            $defaultSelectedFields = $tagCatalogueService->getDefaultListFields();
            $session->set('admin/unesco/selected_fields', $defaultSelectedFields);
        }

        return [
            'selectedFields' => $session->get('admin/unesco/selected_fields'),
        ];
    }

    /**
     * @Route("/custom/fields/add", name="pumukitnewadmin_catalogue_custom_fields")
     *
     * @param Request $request
     *
     * @return JsonResponse
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

    /**
     * @param      $criteria
     * @param null $tag
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function searchMultimediaObjects($criteria, $tag = null)
    {
        $configuredTag = $this->getConfiguredTag();
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $session = $this->get('session');
        $session->set('admin/unesco/tag', $tag);

        $tagCondition = $tag;
        if (isset($tag) && !in_array($tag, ['1', '2'])) {
            $tagCondition = 'tag';
        }

        switch ($tagCondition) {
            case '1':
                // NOTE: Videos without configured tag
                $selectedTag = $dm->getRepository(Tag::class)->findOneBy(['cod' => $configuredTag->getCod()]);
                $query = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
                    ->field('tags._id')
                    ->notEqual(new \MongoId($selectedTag->getId()))
                ;

                break;
            case 'tag':
                $selectedTag = $dm->getRepository(Tag::class)->findOneBy(['cod' => $tag]);
                $query = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder()
                    ->field('tags._id')
                    ->equals(new \MongoId($selectedTag->getId()))
                ;

                break;
            case '2':
            default:
                // NOTE: All videos
                $query = $dm->getRepository(MultimediaObject::class)->createStandardQueryBuilder();

                break;
        }

        if (isset($criteria) && !empty($criteria)) {
            $query = $this->addCriteria($query, $criteria);
        }

        return $query;
    }

    /**
     * @param $query
     * @param $criteria
     *
     * @return mixed
     */
    private function addCriteria($query, $criteria)
    {
        $request = $this->get('request_stack')->getMasterRequest();

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
                    $this->get('pumukitnewadmin.multimedia_object_search')->completeSearchQueryBuilder(
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
                new \MongoDate(strtotime($public_date_init)),
                new \MongoDate(strtotime($public_date_finish))
            );
        } elseif (isset($public_date_init) && !empty($public_date_init)) {
            $date = date($public_date_init.'T23:59:59');
            $query->field('public_date')->range(
                new \MongoDate(strtotime($public_date_init)),
                new \MongoDate(strtotime($date))
            );
        } elseif (isset($public_date_finish) && !empty($public_date_finish)) {
            $date = date($public_date_finish.'T23:59:59');
            $query->field('public_date')->range(
                new \MongoDate(strtotime($public_date_finish)),
                new \MongoDate(strtotime($date))
            );
        }

        if (isset($record_date_init, $record_date_finish)) {
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_init)),
                new \MongoDate(strtotime($record_date_finish))
            );
        } elseif (isset($record_date_init)) {
            $date = date($record_date_init.'T23:59:59');
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_init)),
                new \MongoDate(strtotime($date))
            );
        } elseif (isset($record_date_finish)) {
            $date = date($record_date_finish.'T23:59:59');
            $query->field('record_date')->range(
                new \MongoDate(strtotime($record_date_finish)),
                new \MongoDate(strtotime($date))
            );
        }

        return $query;
    }

    /**
     * @return array
     */
    private function getMmobjsYears()
    {
        $mmObjColl = $this->get('doctrine_mongodb')->getManager()->getDocumentCollection(
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

    /**
     * @param $query
     * @param $key
     * @param $field
     *
     * @return mixed
     */
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
            $end = \DateTime::createFromFormat('d/m/Y:H:i:s', sprintf('01/01/%s:00:00:01', ($field) + 1));
            $query->field('record_date')->gte($start);
            $query->field('record_date')->lt($end);
        }

        return $query;
    }

    private function checkHasEditor()
    {
        $router = $this->get('router');
        $routes = $router->getRouteCollection()->all();

        return array_key_exists('pumukit_videoeditor_index', $routes);
    }

    private function getAllGroups()
    {
        $groupService = $this->get('pumukitschema.group');
        $userService = $this->get('pumukitschema.user');
        $loggedInUser = $this->getUser();
        if ($loggedInUser->isSuperAdmin() || $userService->hasGlobalScope($loggedInUser)) {
            $allGroups = $groupService->findAll();
        } else {
            $allGroups = $loggedInUser->getGroups();
        }

        return $allGroups;
    }

    /**
     * @throws \Exception
     *
     * @return Tag
     */
    private function getConfiguredTag()
    {
        $tagCatalogueService = $this->get('pumukitnewadmin.tag_catalogue');

        return $tagCatalogueService->getConfiguredTag();
    }
}
