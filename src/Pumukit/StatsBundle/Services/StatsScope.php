<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Bundle\SecurityBundle\Security;

final class StatsScope
{
    private ?array $multimediaObjectIds = null;
    private bool $resolved = false;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly Security $security,
        private readonly string $personalScopeRoleCode,
    ) {}

    public function isGlobal(): bool
    {
        return $this->security->isGranted(PermissionProfile::SCOPE_GLOBAL) || $this->security->isGranted('ROLE_SUPER_ADMIN');
    }

    public function multimediaObjectIds(): ?array
    {
        if ($this->resolved) {
            return $this->multimediaObjectIds;
        }
        $this->resolved = true;

        if ($this->isGlobal()) {
            return $this->multimediaObjectIds = null;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->multimediaObjectIds = [];
        }

        $or = [];
        if ($person = $user->getPerson()) {
            $or[] = ['people' => ['$elemMatch' => ['people._id' => new ObjectId($person->getId()), 'cod' => $this->personalScopeRoleCode]]];
        }
        if ($groups = $user->getGroupsIds()) {
            $or[] = ['groups' => ['$in' => $groups]];
        }
        if (!$or) {
            return $this->multimediaObjectIds = [];
        }

        $ids = $this->documentManager->getDocumentCollection(MultimediaObject::class)->distinct('_id', ['$or' => $or]);

        return $this->multimediaObjectIds = array_values($ids);
    }

    public function canSeeMultimediaObject(string $id): bool
    {
        $ids = $this->multimediaObjectIds();

        return null === $ids || in_array($id, array_map('strval', $ids), true);
    }

    public function canSeeSeries(string $id): bool
    {
        if (null === $this->multimediaObjectIds()) {
            return true;
        }

        return (bool) $this->documentManager->getDocumentCollection(MultimediaObject::class)->countDocuments([
            '_id' => ['$in' => $this->multimediaObjectIds()],
            'series' => new ObjectId($id),
        ], ['limit' => 1]);
    }
}
