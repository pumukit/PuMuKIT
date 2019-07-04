<?php

namespace Pumukit\NewAdminBundle\Controller;

use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Utils\Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceController extends Controller
{
    public static $resourceName = 'series';
    public static $repoName = Series::class;

    public function getResourceName()
    {
        return static::$resourceName;
    }

    public function getPluralResourceName()
    {
        return static::$resourceName.'s';
    }

    public function redirectToIndex()
    {
        return $this->redirect($this->generateUrl($this->getRedirectRoute()));
    }

    public function getRepository()
    {
        $dm = $this->container->get('doctrine_mongodb')->getManager();

        return $dm->getRepository(static::$repoName);
    }

    public function getSorting(Request $request = null, $session_namespace = null)
    {
        return [];
    }

    public function findOr404(Request $request, array $criteria = [])
    {
        $default = [];
        if ($request->request->has('slug') || $request->attributes->has('slug') || $request->query->has('slug')) {
            $default = ['slug' => $request->get('slug')];
        } elseif ($request->request->has('id') || $request->attributes->has('id') || $request->query->has('id')) {
            if ('null' !== $request->get('id')) {
                $default = ['id' => $request->get('id')];
            }
        }

        $criteria = array_merge($default, $criteria);

        $repo = $this->getRepository();

        if (!$resource = $repo->findOneBy($criteria)) {
            throw new NotFoundHttpException(
                sprintf(
                    'Requested %s does not exist with these criteria: %s.',
                    $this->getResourceName(),
                    json_encode($criteria)
                )
            );
        }

        return $resource;
    }

    public function update($resource)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $dm->persist($resource);
        $dm->flush();
    }

    public function createNew()
    {
        //trace of remove "sylius/resource-bundle" version 0.12.
        throw new \LogicException('createNew method should be overide in the final Controller.');
    }

    protected function createPager($criteria, $sorting)
    {
        $repo = $this->getRepository();

        $queryBuilder = $repo->createQueryBuilder();

        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->sort($sorting);

        $adapter = new DoctrineODMMongoDBAdapter($queryBuilder);

        return new Pagerfanta($adapter);
    }

    private function getRedirectRoute($routeName = 'index')
    {
        $resourceName = $this->getResourceName();

        return 'pumukitnewadmin_'.$resourceName.'_'.$routeName;
    }
}
