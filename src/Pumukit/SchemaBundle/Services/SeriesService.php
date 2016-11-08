<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Doctrine\ODM\MongoDB\DocumentManager;

class SeriesService
{
    private $dm;
    private $repo;
    private $mmRepo;
    private $seriesDispatcher;

    public function __construct(DocumentManager $documentManager, SeriesEventDispatcherService $seriesDispatcher)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Series');
        $this->mmRepo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->seriesDispatcher = $seriesDispatcher;
    }

    /**
     * Resets the magic url for a given series. Returns the secret id.
     *
     * @param Series
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
        if ($firstFound == null) {
            return false;
        }
        $embeddedBroadcast = $firstFound->getEmbeddedBroadcast();
        if (null == $embeddedBroadcast) {
            return false;
        }
        $type = $embeddedBroadcast->getType();
        if ((EmbeddedBroadcast::TYPE_PUBLIC === $type) || (EmbeddedBroadcast::TYPE_LOGIN === $type)) {
            $count = $this->mmRepo->countInSeriesWithEmbeddedBroadcastType($series, $type);
        } elseif (EmbeddedBroadcast::TYPE_PASSWORD === $type) {
            $count = $this->mmRepo->countInSeriesWithEmbeddedBroadcastPassword($series, $type, $embeddedBroadcast->getPassword());
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $type) {
            $groups = array();
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
}
