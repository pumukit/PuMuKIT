<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\UTCDateTime;
use Pumukit\SchemaBundle\Document\Event;
use Pumukit\SchemaBundle\Document\Live;

/**
 * EventRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventRepository extends DocumentRepository
{
    /**
     * Find next events.
     */
    public function findNextEvents()
    {
        $now = new \DateTime('now');

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->execute();
    }

    /**
     * Find next event.
     */
    public function findNextEvent()
    {
        $now = new \DateTime('now');

        return $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($now)
            ->sort('date', 1)
            ->getQuery()->getSingleResult();
    }

    /**
     * Find event in a month.
     *
     * @param mixed $month
     * @param mixed $year
     */
    public function findInMonth($month, $year)
    {
        $start = new \DateTime(sprintf('%s-%s-01', $year, $month));
        $end = clone $start;
        $end = $end->modify('+1 month');

        return $this->createQueryBuilder()
            ->field('date')->gte($start)
            ->field('date')->lt($end)
            ->sort('date', 1)
            ->getQuery()->execute();
    }

    /**
     * Find current events.
     *
     * @param int|null $limit
     * @param int      $marginBefore
     * @param int      $marginAfter
     *
     * @return array of Events
     */
    public function findCurrentEvents($limit = null, $marginBefore = 0, $marginAfter = 0)
    {
        $dmColl = $this->dm->getDocumentCollection(Event::class);

        $nowWithMarginBefore = new UTCDateTime(strtotime(sprintf('%s minute', $marginBefore)) * 1000);
        $nowWithMarginAfter = new UTCDateTime(strtotime(sprintf('-%s minute', $marginAfter)) * 1000);
        $pipeline = [
            ['$match' => ['display' => true]],
            ['$project' => ['date' => true, 'end' => ['$add' => ['$date', ['$multiply' => ['$duration', 60000]]]]]],
            ['$match' => ['$and' => [['date' => ['$lte' => $nowWithMarginBefore]], ['end' => ['$gte' => $nowWithMarginAfter]]]]],
        ];

        if ($limit) {
            $pipeline[] = ['$limit' => $limit];
        }
        $aggregation = $dmColl->aggregate($pipeline, ['cursor' => []]);
        $aggregation = $aggregation->toArray();
        if (0 === count($aggregation)) {
            return [];
        }

        $ids = array_map(function ($e) {
            return $e['_id'];
        }, $aggregation);

        return $this->createQueryBuilder()
            ->field('_id')->in($ids)
            ->getQuery()->execute();
    }

    /**
     * @param int|null       $limit
     * @param \DateTime|null $date
     * @param Live|null      $live  Find only events of a live channel
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function findFutureAndNotFinished($limit = null, $date = null, Live $live = null)
    {
        // First: look if there is a current live event broadcasting
        // for setting datetime minus duration
        if (!$date) {
            $currentDatetime = new \DateTime();
            $startDay = new \DateTime();
            $finishDay = new \DateTime();
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEventsQB = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1)
        ;

        if ($live) {
            $currentDayEventsQB->field('live')->references($live);
        }

        $currentDayEvents = $currentDayEventsQB->getQuery()->execute();

        $duration = 0;
        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format('Y-m-d H:i:s'));
            if (($eventDate < $currentDatetime) && ($currentDatetime < $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                $duration = $event->getDuration();
            }
        }
        $currentDatetime->sub(new \DateInterval('PT'.$duration.'M'));

        // Second: look for current and next events
        $qb = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($currentDatetime)
            ->sort('date', 1)
        ;

        if ($live) {
            $qb->field('live')->references($live);
        }

        if ($limit) {
            $qb->limit($limit);
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Find one by hours event.
     *
     * @param string|null    $hours
     * @param \DateTime|null $date
     *
     * @throws \Exception
     */
    public function findOneByHoursEvent($hours = null, $date = null)
    {
        if (!$date) {
            $currentDatetime = new \DateTime();
            $hoursDatetime = new \DateTime();
            $startDay = new \DateTime();
            $finishDay = new \DateTime();
        } else {
            $currentDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $hoursDatetime = new \DateTime($date->format('Y-m-d H:s:i'));
            $startDay = new \DateTime($date->format('Y-m-d H:s:i'));
            $finishDay = new \DateTime($date->format('Y-m-d H:s:i'));
        }
        if ($hours) {
            $hoursDatetime->add(new \DateInterval('PT'.$hours.'H'));
        }
        $startDay->setTime(0, 0, 0);
        $finishDay->setTime(23, 59, 59);

        $currentDayEvents = $this->createQueryBuilder()
            ->field('display')->equals(true)
            ->field('date')->gte($startDay)
            ->field('date')->lte($finishDay)
            ->sort('date', 1)
            ->getQuery()->execute();

        foreach ($currentDayEvents as $event) {
            $eventDate = new \DateTime($event->getDate()->format('Y-m-d H:i:s'));
            if (($eventDate <= $hoursDatetime) && ($currentDatetime <= $eventDate->add(new \DateInterval('PT'.$event->getDuration().'M')))) {
                return $event;
            }
        }

        return null;
    }
}
