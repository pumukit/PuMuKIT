<?php

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pagerfanta\Pagerfanta;
use Pumukit\CoreBundle\Services\PaginationService;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResourceController extends AbstractController
{
    public static $resourceName = 'series';
    public static $repoName = Series::class;

    /** @var DocumentManager */
    protected $documentManager;
    /** @var PaginationService */
    protected $paginationService;

    public function __construct(DocumentManager $documentManager, PaginationService $paginationService)
    {
        $this->documentManager = $documentManager;
        $this->paginationService = $paginationService;
    }

    public function getResourceName(): string
    {
        return static::$resourceName;
    }

    public function getPluralResourceName(): string
    {
        return static::$resourceName.'s';
    }

    public function redirectToIndex(): string
    {
        return $this->redirect($this->generateUrl($this->getRedirectRoute()));
    }

    public function getRepository()
    {
        return $this->documentManager->getRepository(static::$repoName);
    }

    public function getSorting(Request $request = null, $session_namespace = null): array
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

    public function update($resource): void
    {
        $this->documentManager->persist($resource);
        $this->documentManager->flush();
    }

    public function createNew(): void
    {
        //trace of remove "sylius/resource-bundle" version 0.12.
        throw new \LogicException('createNew method should be overide in the final Controller.');
    }

    protected function createPager($criteria, $sorting): Pagerfanta
    {
        $repo = $this->getRepository();

        $queryBuilder = $repo->createQueryBuilder();

        $queryBuilder->setQueryArray($criteria);
        $queryBuilder->sort($sorting);

        return $this->paginationService->createDoctrineODMMongoDBAdapter($queryBuilder);
    }

    private function getRedirectRoute($routeName = 'index'): string
    {
        $resourceName = $this->getResourceName();

        return 'pumukitnewadmin_'.$resourceName.'_'.$routeName;
    }
}
