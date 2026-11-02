<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Doctrine;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\FilterService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\SecurityBundle\Security;

final class ScopeExtension implements AggregationCollectionExtensionInterface, AggregationItemExtensionInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly FilterService $filterService,
        private readonly Security $security,
    ) {}

    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $this->apply($aggregationBuilder, $resourceClass);
    }

    public function applyToItem(Builder $aggregationBuilder, string $resourceClass, array $identifiers, ?Operation $operation = null, array &$context = []): void
    {
        $this->apply($aggregationBuilder, $resourceClass);
    }

    private function apply(Builder $aggregationBuilder, string $resourceClass): void
    {
        if (MultimediaObject::class !== $resourceClass && Series::class !== $resourceClass) {
            return;
        }

        if (MultimediaObject::class === $resourceClass) {
            $aggregationBuilder->match()->field('status')->notEqual(MultimediaObject::STATUS_PROTOTYPE);
        }

        if ($this->security->isGranted(PermissionProfile::SCOPE_GLOBAL)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$this->security->isGranted(PermissionProfile::SCOPE_PERSONAL)) {
            $aggregationBuilder->match()->field('_id')->exists(false);

            return;
        }

        $filters = $this->documentManager->getFilterCollection();
        if (!$filters->isEnabled('backoffice')) {
            $this->filterService->setAdminParameters($filters->enable('backoffice'), $user);
        }
    }
}
