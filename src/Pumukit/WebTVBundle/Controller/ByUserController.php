<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\User;

class ByUserController extends Controller implements WebTVController
{
    /**
     * @Route("/users/{username}", name="pumukit_webtv_byuser_multimediaobjects", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:ByUser:index.html.twig")
     */
    public function multimediaObjectsAction(User $user, Request $request)
    {
        list($scroll_list, $numberCols, $limit, $roleCode) = $this->getParameters();
        $person = $user->getPerson();

        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $mmobjs = $repo->createBuilderByPersonIdWithRoleCod($person->getId(), $roleCode, array('public_date' => -1));
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_multimediaobjects', array('username' => $user->getUsername()));
        $title = $user->getFullname();

        $pagerfanta = $this->createPager($mmobjs, $request->query->get('page', 1), $limit);

        $title = $this->get('translator')->trans('%title%', array('%title%' => $title));

        return array(
            'title' => $title,
            'objects' => $pagerfanta,
            'user' => $user,
            'scroll_list' => $scroll_list,
            'number_cols' => $numberCols,
            'type' => 'multimediaobject',
        );
    }

    /**
     * @Route("/users/{username}/series",  name="pumukit_webtv_byuser_series", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:ByUser:index.html.twig")
     */
    public function seriesAction(User $user, Request $request)
    {
        list($scroll_list, $numberCols, $limit, $roleCode) = $this->getParameters();
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $person = $user->getPerson();
        $series = $repo->createBuilderByPersonIdAndRoleCod($person->getId(), $roleCode, array('public_date' => -1));

        $pagerfanta = $this->createPager($series, $request->query->get('page', 1));
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_series', array('username' => $user->getUsername()));

        $title = $user->getFullname();
        $title = $this->get('translator')->trans('%title% (Series)', array('%title%' => $title));

        return array(
            'title' => $title,
            'objects' => $pagerfanta,
            'user' => $user,
            'scroll_list' => $scroll_list,
            'number_cols' => $numberCols,
            'type' => 'series',
        );
    }

    /**
     * @Route("/users/{username}/pager/{type}", name="pumukit_webtv_byuser_objects_pager", defaults={"username": null, "type": "multimediaobject"})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template()
     */
    public function userObjectsPager(User $user, Request $request)
    {
        list($scroll_list, $numberCols, $limit, $roleCode) = $this->getParameters();
        $type = $request->get('type');
        $person = $user->getPerson();

        $dateRequest = $request->query->get('date', 0); //Use to queries for month and year to reduce formatting and unformatting.
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', "01/$dateRequest 00:00:00");
        if (!$date) {
            throw $this->createNotFoundException();
        }

        if ($type === 'multimediaobject') {
            $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
            $qb = $repo->createBuilderByPersonIdWithRoleCod($person->getId(), $roleCode, array('public_date' => -1));
        } else {
            $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
            $qb = $repo->createBuilderByPersonIdAndRoleCod($person->getId(), $roleCode, array('public_date' => -1));
        }

        list($date, $last) = $this->getNextLatestUploads($date, $qb);
        if (empty($last)) {
            $dateHeader = '---';
        } else {
            $dateHeader = $date->format('m/Y');
        }

        $response = new Response($this->renderView('PumukitWebTVBundle:ByUser:list.html.twig', array(
            'objects' => $last,
            'date' => $date,
            'number_cols' => $numberCols,
        )), 200);
        $response->headers->set('X-Date', $dateHeader);
        $response->headers->set('X-Date-Month', $date->format('m'));
        $response->headers->set('X-Date-Year', $date->format('Y'));

        return $response;
    }

    /**
     * To extends this controller.
     */
    protected function getParameters()
    {
        return array(
            $this->container->getParameter('scroll_list_byuser'),
            $this->container->getParameter('columns_objs_byuser'),
            $this->container->getParameter('limit_objs_byuser'),
            $this->container->getParameter('pumukitschema.personal_scope_role_code'),
        );
    }

    private function updateBreadcrumbs($title, $routeName, array $routeParameters = array())
    {
        $breadcrumbs = $this->get('pumukit_web_tv.breadcrumbs');
        $breadcrumbs->add($title, $routeName, $routeParameters);
    }

    private function createPager($objects, $page, $limit = 10)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    protected function getLatestUploadsByDates($dateStart, $dateEnd, $qb)
    {
        $qb->field('public_date')->range($dateStart, $dateEnd);

        return $qb->sort(array('public_date' => -1))->getQuery()->execute()->toArray();
    }

    /**
     * Gets the next latest uploads month, starting with the month given and looking 24 months forward.
     *
     * If it can't find any objects, returns an empty array.
     *
     * @return array
     */
    protected function getNextLatestUploads($date, $qb)
    {
        $counter = 0;
        $dateStart = clone $date;
        $dateStart->modify('first day of next month');
        $dateEnd = clone $date;
        $dateEnd->modify('last day of next month');
        $dateEnd->setTime(23, 59, 59);
        do {
            ++$counter;
            $dateStart->modify('first day of last month');
            $dateEnd->modify('last day of last month');
            $last = $this->getLatestUploadsByDates($dateStart, $dateEnd, $qb);
        } while (empty($last) && $counter < 24);

        return array($dateEnd, $last);
    }
}
