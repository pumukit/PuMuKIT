<?php

namespace Pumukit\WebTVBundle\Controller;

use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ListController.
 */
class ListController extends Controller implements WebTVControllerInterface
{
    /**
     * @Route("/multimediaobjects/tag/{tagCod}", name="pumukit_webtv_bytag_multimediaobjects", defaults={"tagCod": null})
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:List:template.html.twig")
     *
     * @param Tag     $tag
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function multimediaObjectsByTagAction(Tag $tag, Request $request)
    {
        [$scrollList, $numberCols, $limit] = $this->getParametersByTag();

        $multimediaObjectRepository = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(MultimediaObject::class);

        $breadCrumbOptions = ['tagCod' => $tag->getCod()];
        if ($request->get('useTagAsGeneral')) {
            $objects = $multimediaObjectRepository->createBuilderWithGeneralTag($tag, ['record_date' => -1]);
            $title = $this->get('translator')->trans('General %title%', ['%title%' => $tag->getTitle()]);
            $breadCrumbOptions['useTagAsGeneral'] = true;
        } else {
            $objects = $multimediaObjectRepository->createBuilderWithTag($tag, ['record_date' => -1]);
            $title = $tag->getTitle();
        }
        $this->updateBreadcrumbs($title, 'pumukit_webtv_bytag_multimediaobjects', ['tagCod' => $tag->getCod(), 'useTagAsGeneral' => true]);

        $pager = $this->createPager($objects, $request->query->get('page', 1), $limit);

        $title = $this->get('translator')->trans('Multimedia objects with tag: %title%', [
            '%title%' => $title,
        ]);

        return [
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
        ];
    }

    /**
     * @Route("/series/tag/{tagCod}",  name="pumukit_webtv_bytag_series", defaults={"tagCod": null})
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     * @Template("PumukitWebTVBundle:List:template.html.twig")
     *
     * @param Tag     $tag
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function seriesByTagAction(Tag $tag, Request $request)
    {
        [$scrollList, $numberCols, $limit] = $this->getParametersByTag();

        $seriesRepository = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(Series::class);
        $series = $seriesRepository->createBuilderWithTag($tag, ['public_date' => -1]);

        $pager = $this->createPager($series, $request->query->get('page', 1), $limit);

        $this->updateBreadcrumbs($tag->getTitle(), 'pumukit_webtv_bytag_series', [
            'tagCod' => $tag->getCod(),
        ]);

        $title = $this->get('translator')->trans('Series with tag: %title%', ['%title%' => $tag->getTitle()]);

        return [
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
        ];
    }

    /**
     * @Route("/users/{username}", name="pumukit_webtv_byuser_multimediaobjects", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:List:template.html.twig")
     *
     * @param User    $user
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function multimediaObjectsByUserAction(User $user, Request $request)
    {
        [$scrollList, $numberCols, $limit, $roleCode] = $this->getParametersByUser();
        $person = $user->getPerson();

        $multimediaObjectRepository = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

        $objects = $multimediaObjectRepository->createBuilderByPersonIdWithRoleCod($person->getId(), $roleCode, ['public_date' => -1]);
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_multimediaobjects', ['username' => $user->getUsername()]);

        $pager = $this->createPager($objects, $request->query->get('page', 1), $limit);

        $title = $user->getFullname();

        return [
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
        ];
    }

    /**
     * @Route("/users/{username}/series",  name="pumukit_webtv_byuser_series", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:List:template.html.twig")
     *
     * @param User    $user
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function seriesByUserAction(User $user, Request $request)
    {
        [$scrollList, $numberCols, $limit, $roleCode] = $this->getParametersByUser();

        $seriesRepository = $this->get('doctrine_mongodb')->getRepository(Series::class);
        $person = $user->getPerson();
        $series = $seriesRepository->createBuilderByPersonIdAndRoleCod($person->getId(), $roleCode, ['public_date' => -1]);

        $pager = $this->createPager($series, $request->query->get('page', 1), $limit);
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_series', ['username' => $user->getUsername()]);

        $title = $user->getFullname();

        return [
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
        ];
    }

    /**
     * @Route("/users/{username}/pager/{type}", name="pumukit_webtv_byuser_objects_pager", defaults={"username": null, "type": "multimediaobject"})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     *
     * @param User    $user
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function userObjectsPagerAction(Request $request, User $user)
    {
        [$scroll_list, $numberCols, $limit, $roleCode] = $this->getParametersByUser();

        $type = $request->get('type');

        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");

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

        $qb = $this->get('doctrine_mongodb.odm.document_manager')->getRepository($class)->$method(
            $person->getId(),
            $roleCode,
            ['public_date' => -1]
        );

        [$date, $last] = $this->get('pumukit_web_tv.list_service')->getNextElementsByQueryBuilder($qb, $date);

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
            ), 200
        );
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }

    /**
     * @Route("/bytag/{tagCod}/pager/{type}", name="pumukit_webtv_bytag_objects_pager", defaults={"tagCod": null, "type": "multimediaobject"})
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"mapping": {"tagCod": "cod"}})
     *
     * @param Request $request
     * @param Tag     $tag
     *
     * @return Response
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function byTagObjectsPagerAction(Request $request, Tag $tag)
    {
        [$scroll_list, $numberCols, $limit] = $this->getParametersByTag();

        $type = $request->get('type');

        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");

        if (!$date) {
            throw $this->createNotFoundException();
        }

        $class = MultimediaObject::class;
        if ('series' === $type) {
            $class = Series::class;
        }

        $qb = $this->get('doctrine_mongodb.odm.document_manager')->getRepository($class)->createBuilderWithTag($tag, ['public_date' => -1]);

        [$date, $last] = $this->get('pumukit_web_tv.list_service')->getNextElementsByQueryBuilder($qb, $date);

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
            ), 200
        );
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }

    /**
     * @return string
     */
    protected function getPagerTemplate()
    {
        return 'PumukitWebTVBundle:List:template_pager.html.twig';
    }

    /**
     * @param       $title
     * @param       $routeName
     * @param array $routeParameters
     */
    private function updateBreadcrumbs($title, $routeName, array $routeParameters = [])
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }

    /**
     * @param     $objects
     * @param     $page
     * @param int $limit
     *
     * @return mixed|Pagerfanta
     *
     * @throws \Exception
     */
    private function createPager($objects, $page, $limit = 10)
    {
        $pager = $this->get('pumukit_web_tv.pagination_service')->createDoctrineODMMongoDBAdapter($objects, $page, $limit);

        return $pager;
    }

    /**
     * To extends this controller.
     */
    protected function getParametersByUser()
    {
        return [
            $this->container->getParameter('scroll_list_byuser'),
            $this->container->getParameter('columns_objs_byuser'),
            $this->container->getParameter('limit_objs_byuser'),
            $this->container->getParameter('pumukitschema.personal_scope_role_code'),
        ];
    }

    protected function getParametersByTag()
    {
        return [
            $this->container->getParameter('scroll_list_bytag'),
            $this->container->getParameter('columns_objs_bytag'),
            $this->container->getParameter('limit_objs_bytag'),
        ];
    }
}
