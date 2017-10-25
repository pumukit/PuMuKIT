<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\User;

class ByUserController extends Controller implements WebTVController
{
    /**
     * @Route("/multimediaobjects/user/{username}", name="pumukit_webtv_byuser_multimediaobjects", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:ByUser:index.html.twig")
     */
    public function multimediaObjectsAction(User $user, Request $request)
    {
        $numberCols = $this->container->getParameter('columns_objs_byuser');
        $limit = $this->container->getParameter('limit_objs_byuser');
        $roleCode = $this->container->getParameter('pumukitschema.personal_scope_role_code');
        $person = $user->getPerson();

        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        $mmobjs = $repo->createBuilderByPersonIdWithRoleCod($person->getId(), $roleCode, array('record_date' => -1));
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_multimediaobjects', array('fullname' => $user->getFullname()));
        $title = $user->getFullname();

        $pagerfanta = $this->createPager($mmobjs, $request->query->get('page', 1), $limit);

        $title = $this->get('translator')->trans('Multimedia objects with user: %title%', array('%title%' => $title));

        return array(
            'title' => $title,
            'objects' => $pagerfanta,
            'user' => $user,
            'number_cols' => $numberCols,
        );
    }

    /**
     * @Route("/series/user/{username}",  name="pumukit_webtv_byuser_series", defaults={"username": null})
     * @ParamConverter("user", class="PumukitSchemaBundle:User", options={"mapping": {"username": "username"}})
     * @Template("PumukitWebTVBundle:ByUser:index.html.twig")
     */
    public function seriesAction(User $user, Request $request)
    {
        $numberCols = $this->container->getParameter('columns_objs_byuser');
        $repo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $roleCode = $this->container->getParameter('pumukitschema.personal_scope_role_code');
        $person = $user->getPerson();
        $series = $repo->createBuilderByPersonIdAndRoleCod($person->getId(), $roleCode, array('public_date' => -1));

        $pagerfanta = $this->createPager($series, $request->query->get('page', 1));
        $this->updateBreadcrumbs($user->getFullname(), 'pumukit_webtv_byuser_series', array('fullname' => $user->getFullname()));

        $title = $user->getFullname();
        $title = $this->get('translator')->trans('Series with user: %title%', array('%title%' => $title));

        return array(
            'title' => $title,
            'objects' => $pagerfanta,
            'user' => $user,
            'number_cols' => $numberCols,
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
}
