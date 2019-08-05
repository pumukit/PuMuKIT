<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;

class SeriesService
{
    private $dm;
    private $repo;
    private $mmRepo;
    private $seriesDispatcher;

    public function __construct(DocumentManager $documentManager, SeriesEventDispatcherService $seriesDispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository(Series::class);
        $this->mmRepo = $this->dm->getRepository(MultimediaObject::class);
        $this->seriesDispatcher = $seriesDispatcher;
    }

    /**
     * Resets the magic url for a given series. Returns the secret id.
     *
     * @param Series $series
     *
     * @return string
     */
    public function resetMagicUrl($series)
    {
        $series->resetSecret();
        $this->dm->persist($series);
        $this->dm->flush();
        $this->seriesDispatcher->dispatchUpdate($series);

        return $series->getSecret();
    }

    /**
     * Same Embedded Broadcast.
     *
     * @param Series $series
     *
     * @return bool
     */
    public function sameEmbeddedBroadcast(Series $series)
    {
        if (0 == $this->mmRepo->countInSeriesWithPrototype($series)) {
            return false;
        }
        $firstFound = null;
        $all = $this->mmRepo->findBySeries($series);
        foreach ($all as $multimediaObject) {
            $firstFound = $multimediaObject;

            break;
        }
        if (null === $firstFound) {
            return false;
        }
        $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
        if (null === $embeddedBroadcast) {
            return false;
        }
        $type = $embeddedBroadcast->getType();
        if ((EmbeddedBroadcast::TYPE_PUBLIC === $type) || (EmbeddedBroadcast::TYPE_LOGIN === $type)) {
            $count = $this->mmRepo->countInSeriesWithEmbeddedBroadcastType($series, $type);
        } elseif (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $count = $this->mmRepo->countInSeriesWithEmbeddedBroadcastPassword($series, $type, $embeddedBroadcast->getPassword());
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
            $groups = [];
            foreach ($embeddedBroadcast->getGroups() as $group) {
                $groups[] = new \MongoId($group->getId());
            }
            $count = $this->mmRepo->countInSeriesWithEmbeddedBroadcastGroups($series, $type, $groups);
        } else {
            $count = 0;
        }
        $total = $this->mmRepo->countInSeriesWithPrototype($series);

        return $total === $count;
    }

    /**
     * Get Series of User.
     * A User is owner of a Series
     * if the Series has some multimedia
     * object where the user is, as person,
     * with owner role code or share groups
     * with the multimedia object.
     *
     * @param User   $user
     * @param bool   $onlyAdminSeries
     * @param string $roleOwnerCode
     * @param array  $sort
     * @param int    $limit
     *
     * @return array
     */
    public function getSeriesOfUser(User $user, $onlyAdminSeries = false, $roleOwnerCode = '', $sort = [], $limit = 0)
    {
        if (($permissionProfile = $user->getPermissionProfile()) && $permissionProfile->isGlobal() && !$onlyAdminSeries) {
            return $this->repo->findBy([], $sort, $limit);
        }
        $groups = [];
        foreach ($user->getGroups() as $group) {
            $groups[] = $group->getId();
        }

        return $this->repo->findByPersonIdAndRoleCodOrGroupsSorted($user->getPerson()->getId(), $roleOwnerCode, $groups, $sort, $limit);
    }
}
