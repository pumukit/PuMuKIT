<?php

declare(strict_types=1);

namespace Pumukit\WebTVBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Repository\MultimediaObjectRepository;
use Pumukit\SchemaBundle\Repository\SeriesRepository;
use Pumukit\WebTVBundle\Services\BreadcrumbsService;
use Pumukit\WebTVBundle\Services\ListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListController extends AbstractController implements WebTVControllerInterface
{
    protected $documentManager;
    protected $breadcrumbsService;
    protected $translator;
    protected $listService;
    protected $paginationService;

    protected $scrollListByUser;
    protected $columnsObjsByUser;
    protected $limitObjsByUser;

    protected $pumukitSchemaPersonalScopeRoleCode;
    protected $scrollListByTag;
    protected $columnsObjsByTag;
    protected $limitObjsByTag;

    public function __construct(
        DocumentManager $documentManager,
        BreadcrumbsService $breadcrumbsService,
        TranslatorInterface $translator,
        ListService $listService,
        PaginationService $paginationService,
        $scrollListByUser,
        $columnsObjsByUser,
        $limitObjsByUser,
        $pumukitSchemaPersonalScopeRoleCode,
        $scrollListByTag,
        $columnsObjsByTag,
        $limitObjsByTag
    ) {
        $this->documentManager = $documentManager;
        $this->breadcrumbsService = $breadcrumbsService;
        $this->translator = $translator;
        $this->listService = $listService;
        $this->paginationService = $paginationService;
        $this->scrollListByUser = $scrollListByUser;
        $this->columnsObjsByUser = $columnsObjsByUser;
        $this->limitObjsByUser = $limitObjsByUser;
        $this->pumukitSchemaPersonalScopeRoleCode = $pumukitSchemaPersonalScopeRoleCode;
        $this->scrollListByTag = $scrollListByTag;
        $this->columnsObjsByTag = $columnsObjsByTag;
        $this->limitObjsByTag = $limitObjsByTag;
    }

    /**
     * @Route("/multimediaobjects/tag/{tagCod}", name="pumukit_webtv_bytag_multimediaobjects", defaults={"tagCod"=null})
     *
     * @ParamConverter("tag", options={"mapping": {"tagCod": "cod"}})
     */
    public function multimediaObjectsByTagAction(Request $request, Tag $tag): Response
    {
        [$scrollList, $numberCols, $limit] = $this->getParametersByTag();

        $multimediaObjectRepository = $this->documentManager->getRepository(MultimediaObject::class);

        $breadCrumbOptions = ['tagCod' => $tag->getCod()];
        if ($request->get('useTagAsGeneral')) {
            $objects = $multimediaObjectRepository->createBuilderWithGeneralTag($tag, ['record_date' => -1]);
            $title = $this->translator->trans('General %title%', ['%title%' => $tag->getTitle()]);
            $breadCrumbOptions['useTagAsGeneral'] = true;
        } else {
            $objects = $multimediaObjectRepository->createBuilderWithTag($tag, ['record_date' => -1]);
            $title = $tag->getTitle();
        }
        $this->updateBreadcrumbs($title, 'pumukit_webtv_bytag_multimediaobjects', $breadCrumbOptions);

        $pager = $this->createPager($objects, (int) $request->query->get('page', 1), $limit);

        $title = $this->translator->trans('Multimedia objects with tag: %title%', [
            '%title%' => $title,
        ]);

        return $this->render('@PumukitWebTV/List/template.html.twig', [
            'title' => $title,
            'objects' => $pager,
            'tag' => $tag,
            'scroll_list' => $scrollList,
            'type' => 'multimediaobject',
            'scroll_list_path' => 'pumukit_webtv_bytag_objects_pager',
            'scroll_element_key' => 'tagCod',
            'scroll_element_value' => $tag->getCod(),
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => true,
        ]);
    }

    /**
     * @Route("/series/tag/{tagCod}", name="pumukit_webtv_bytag_series", defaults={"tagCod"=null})
     *
     * @ParamConverter("tag", options={"mapping": {"tagCod": "cod"}})
     */
    public function seriesByTagAction(Request $request, Tag $tag): Response
    {
        [$scrollList, $numberCols, $limit] = $this->getParametersByTag();

        $series = $this->documentManager->getRepository(Series::class)->createBuilderWithTag($tag, ['public_date' => -1]);

        $pager = $this->createPager($series, (int) $request->query->get('page', 1), $limit);

        $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_series', [
            'tagCod' => $tag->getCod(),
        ]);

        $title = $this->translator->trans('Series with tag: %title%', ['%title%' => $tag->getTitle()]);

        return $this->render('@PumukitWebTV/List/template.html.twig', [
            'title' => $title,
            'objects' => $pager,
            'tag' => $tag,
            'scroll_list' => $scrollList,
            'type' => 'series',
            'scroll_list_path' => 'pumukit_webtv_bytag_objects_pager',
            'scroll_element_key' => 'tagCod',
            'scroll_element_value' => $tag->getCod(),
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => false,
        ]);
    }

    /**
     * @Route("/users/{username}", name="pumukit_webtv_byuser_multimediaobjects", defaults={"username"=null})
     *
     * @ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function multimediaObjectsByUserAction(Request $request, User $user): Response
    {
        [$scrollList, $numberCols, $limit, $roleCode] = $this->getParametersByUser();
        $person = $user->getPerson();

        $multimediaObjectRepository = $this->documentManager->getRepository(MultimediaObject::class);

        $objects = $multimediaObjectRepository->createBuilderByPersonIdWithRoleCod($person->getId(), $roleCode, ['public_date' => -1]);
        $this->updateBreadcrumbs($user->getFullName(), 'pumukit_webtv_byuser_multimediaobjects', ['username' => $user->getUsername()]);

        $pager = $this->createPager($objects, (int) $request->query->get('page', 1), $limit);

        $title = $user->getFullName();

        return $this->render('@PumukitWebTV/List/template.html.twig', [
            'title' => $title,
            'objects' => $pager,
            'user' => $user,
            'scroll_list' => $scrollList,
            'type' => 'multimediaobject',
            'scroll_list_path' => 'pumukit_webtv_byuser_objects_pager',
            'scroll_element_key' => 'username',
            'scroll_element_value' => $user->getUsername(),
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => false,
        ]);
    }

    /**
     * @Route("/users/{username}/series", name="pumukit_webtv_byuser_series", defaults={"username"=null})
     *
     * @ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function seriesByUserAction(Request $request, User $user): Response
    {
        [$scrollList, $numberCols, $limit, $roleCode] = $this->getParametersByUser();

        $seriesRepository = $this->documentManager->getRepository(Series::class);
        $person = $user->getPerson();
        $series = $seriesRepository->createBuilderByPersonIdAndRoleCod($person->getId(), $roleCode, ['public_date' => -1]);

        $pager = $this->createPager($series, (int) $request->query->get('page', 1), $limit);
        $this->updateBreadcrumbs($user->getFullName(), 'pumukit_webtv_byuser_series', ['username' => $user->getUsername()]);

        $title = $user->getFullName();

        return $this->render('@PumukitWebTV/List/template.html.twig', [
            'title' => $title,
            'objects' => $pager,
            'user' => $user,
            'scroll_list' => $scrollList,
            'type' => 'series',
            'scroll_list_path' => 'pumukit_webtv_byuser_objects_pager',
            'scroll_element_key' => 'username',
            'scroll_element_value' => $user->getUsername(),
            'objectByCol' => $numberCols,
            'show_info' => true,
            'show_description' => false,
        ]);
    }

    /**
     * @Route("/users/{username}/pager/{type}", name="pumukit_webtv_byuser_objects_pager", defaults={"username": null, "type": "multimediaobject"})
     *
     * @ParamConverter("user", options={"mapping": {"username": "username"}})
     */
    public function userObjectsPagerAction(Request $request, User $user): Response
    {
        [$scroll_list, $numberCols, $limit, $roleCode] = $this->getParametersByUser();

        $type = $request->get('type');

        $dateRequest = $request->query->get('date', 0); // Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/{$dateRequest} 00:00:00");

        if (!$date) {
            throw $this->createNotFoundException();
        }

        $person = $user->getPerson();
        $class = MultimediaObject::class;
        $method = 'createBuilderByPersonIdWithRoleCod';
        if ('series' === $type) {
            $class = Series::class;
            $method = 'createBuilderByPersonIdAndRoleCod';
        }

        $qb = $this->documentManager->getRepository($class)->{$method}(
            $person->getId(),
            $roleCode,
            ['public_date' => -1]
        );

        return $this->generateResponse($qb, $date, $numberCols);
    }

    /**
     * @Route("/bytag/{tagCod}/pager/{type}", name="pumukit_webtv_bytag_objects_pager", defaults={"tagCod": null, "type": "multimediaobject"})
     *
     * @ParamConverter("tag", options={"mapping": {"tagCod": "cod"}})
     */
    public function byTagObjectsPagerAction(Request $request, Tag $tag): Response
    {
        [$scroll_list, $numberCols, $limit] = $this->getParametersByTag();

        $type = $request->get('type');

        $dateRequest = $request->query->get('date', 0); // Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/{$dateRequest} 00:00:00");

        if (!$date) {
            throw $this->createNotFoundException();
        }

        $class = MultimediaObject::class;
        if ('series' === $type) {
            $class = Series::class;
        }

        /** @var MultimediaObjectRepository|SeriesRepository $qb */
        $qb = $this->documentManager->getRepository($class);
        $qb->createBuilderWithTag($tag, ['public_date' => -1]);

        return $this->generateResponse($qb, $date, $numberCols);
    }

    protected function getPagerTemplate(): string
    {
        return '@PumukitWebTV/List/template_pager.html.twig';
    }

    protected function getParametersByUser(): array
    {
        return [
            $this->scrollListByUser,
            $this->columnsObjsByUser,
            $this->limitObjsByUser,
            $this->pumukitSchemaPersonalScopeRoleCode,
        ];
    }

    protected function getParametersByTag(): array
    {
        return [
            $this->scrollListByTag,
            $this->columnsObjsByTag,
            $this->limitObjsByTag,
        ];
    }

    private function updateBreadcrumbs(?string $title, string $routeName, array $routeParameters = []): void
    {
        $this->breadcrumbsService->add($title, $routeName, $routeParameters);
    }

    private function createPager($objects, int $page, int $limit = 10): Pagerfanta
    {
        return $this->paginationService->createDoctrineODMMongoDBAdapter($objects, $page, $limit);
    }

    private function generateResponse($qb, $date, $numberCols): Response
    {
        [$date, $last] = $this->listService->getNextElementsByQueryBuilder($qb, $date);

        if (empty($last)) {
            $dateHeader = '---';
        } else {
            $dateHeader = $date->format('m/Y');
        }

        $response = new Response(
            $this->renderView(
                $this->getPagerTemplate(),
                [
                    'objects' => $last,
                    'date' => $date,
                    'objectByCol' => $numberCols,
                    'show_info' => true,
                    'show_description' => false,
                ]
            ),
            200
        );
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }
}
