<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use Pumukit\CoreBundle\Services\SerializerService;
use Pumukit\NewAdminBundle\Controller\NewAdminControllerInterface;
use Pumukit\SchemaBundle\Document\Live;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\SeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/media")
 */
class APIController extends AbstractController implements NewAdminControllerInterface
{
    public const API_SKIP = 0;

    protected $documentManager;
    protected $serializer;
    protected $seriesService;
    protected $personService;

    public function __construct(
        DocumentManager $documentManager,
        SerializerService $serializer,
        SeriesService $seriesService,
        PersonService $personService
    ) {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
        $this->seriesService = $seriesService;
        $this->personService = $personService;
    }

    /**
     * @Route("/stats.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function statsAction(Request $request)
    {
        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);
        $seriesRepo = $this->documentManager->getRepository(Series::class);
        $liveRepo = $this->documentManager->getRepository(Live::class);

        $totalSeries = $seriesRepo->countPublic();
        $totalMmobjs = $mmRepo->count();
        $totalHours = round($mmRepo->countDuration() / 3600, 2);
        $totalLiveChannels = $liveRepo->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute()
        ;

        $counts = [
            'series' => $totalSeries,
            'mms' => $totalMmobjs,
            'hours' => $totalHours,
            'live_channels' => $totalLiveChannels,
        ];

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/mmobj.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function multimediaObjectsAction(Request $request)
    {
        $tempCriteria = [];
        $mmRepo = $this->documentManager->getRepository(MultimediaObject::class);

        $limit = (int) $request->get('limit');
        $page = (int) $request->get('page');
        $skip = (int) ($request->get('skip') ?? self::API_SKIP);

        try {
            $criteria = $this->getMultimediaObjectCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Invalid criteria (%s)', $e->getMessage())];
            $data = $this->serializer->dataSerialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: [];
        if (!is_array($sort)) {
            $sort = ['public_date' => $sort];
        }
        $prototypes = $request->get('prototypes') ?: false;

        if (!$limit || $limit > 100) {
            $limit = 100;
        }
        if ($page && $page > 0) {
            $limit = $limit ?: 10;
            $skip = $limit * ($page - 1);
        } else {
            $page = null;
        }

        if ($prototypes) {
            $qb = $mmRepo->createQueryBuilder();
        } else {
            $qb = $mmRepo->createStandardQueryBuilder();
        }

        //  WA TTK-25379 - Add dates range
        if ($criteria) {
            if (isset($criteria['owner'])) {
                $user = $this->documentManager->getRepository(User::class)->createQueryBuilder()->field('username')->equals($criteria['owner'])->getQuery()->getSingleResult();
                $qb->addAnd($qb->expr()->field('people')->elemMatch(
                    $qb->expr()->field('cod')->equals('owner')->field('people.id')->equals($user->getPerson()->getId())
                ));
                $tempCriteria['owner'] = $criteria['owner'];
                unset($criteria['owner']);
            }
            if (isset($criteria['public_date_init'], $criteria['public_date_finish'])) {
                $qb->addAnd($qb->expr()->field('public_date')->range(
                    new UTCDateTime(strtotime($criteria['public_date_init'])),
                    new UTCDateTime(strtotime($criteria['public_date_finish']))
                ));
                $tempCriteria['public_date_init'] = $criteria['public_date_init'];
                $tempCriteria['public_date_finish'] = $criteria['public_date_finish'];
                unset($criteria['public_date_init'], $criteria['public_date_finish']);
            } elseif (isset($criteria['public_date_init']) && !empty($criteria['public_date_init'])) {
                $date = date($criteria['public_date_init'].'T23:59:59');
                $qb->addAnd($qb->expr()->field('public_date')->range(
                    new UTCDateTime(strtotime($criteria['public_date_init'])),
                    new UTCDateTime(strtotime($date))
                ));
                $tempCriteria['public_date_init'] = $criteria['public_date_init'];
                unset($criteria['public_date_init']);
            } elseif (isset($criteria['public_date_finish']) && !empty($criteria['public_date_finish'])) {
                $date = date($criteria['public_date_finish'].'T23:59:59');
                $qb->addAnd($qb->expr()->field('public_date')->range(
                    new UTCDateTime(strtotime($criteria['public_date_finish'])),
                    new UTCDateTime(strtotime($date))
                ));
                $tempCriteria['public_date_finish'] = $criteria['public_date_finish'];
                unset($criteria['public_date_finish']);
            }
            if (isset($criteria['record_date_init'], $criteria['record_date_finish'])) {
                $qb->addAnd($qb->expr()->field('record_date')->range(
                    new UTCDateTime(strtotime($criteria['record_date_init'])),
                    new UTCDateTime(strtotime($criteria['record_date_finish']))
                ));
                $tempCriteria['record_date_init'] = $criteria['record_date_init'];
                $tempCriteria['record_date_finish'] = $criteria['record_date_finish'];
                unset($criteria['record_date_init'], $criteria['record_date_finish']);
            } elseif (isset($criteria['record_date_init']) && !empty($criteria['record_date_init'])) {
                $date = date($criteria['record_date_init'].'T23:59:59');
                $qb->addAnd($qb->expr()->field('record_date')->range(
                    new UTCDateTime(strtotime($criteria['record_date_init'])),
                    new UTCDateTime(strtotime($date))
                ));
                $tempCriteria['record_date_init'] = $criteria['record_date_init'];
                unset($criteria['record_date_init']);
            } elseif (isset($criteria['record_date_finish']) && !empty($criteria['record_date_finish'])) {
                $date = date($criteria['record_date_finish'].'T23:59:59');
                $qb->addAnd($qb->expr()->field('record_date')->range(
                    new UTCDateTime(strtotime($criteria['record_date_finish'])),
                    new UTCDateTime(strtotime($date))
                ));
                $tempCriteria['record_date_finish'] = $criteria['record_date_finish'];
                unset($criteria['record_date_finish']);
            }
            if ($criteria) {
                foreach ($criteria as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $sub_key => $sub_val) {
                            $val[$sub_key] = preg_replace("/[^a-zA-Z áéíóúÁÉÍÓÚñÑ0-9]+/", "", $sub_val);
                            $criteria[$key] = $val;
                        }
                    } else {
                        $criteria[$key] = preg_replace("/[^a-zA-Z áéíóúÁÉÍÓÚñÑ0-9]+/", "", $val);
                    }
                }
                $qb->addAnd($criteria);
            }
            if (!empty($tempCriteria)) {
                $criteria = array_merge($criteria, $tempCriteria);
            }
        }

        $qb_mmobjs = clone $qb;
        $qb_mmobjs = $qb_mmobjs->limit($limit)
            ->skip($skip)
            ->sort($sort)
        ;

        $total = $qb->count()->getQuery()->execute();
        $mmobjs = $qb_mmobjs->getQuery()->execute();

        $counts = [
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'mmobjs' => $mmobjs,
        ];

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function seriesAction(Request $request)
    {
        $seriesRepo = $this->documentManager->getRepository(Series::class);
        $limit = (int) $request->get('limit');
        $page = (int) $request->get('page');
        $skip = (int) ($request->get('skip') ?? self::API_SKIP);

        try {
            $criteria = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Invalid criteria (%s)', $e->getMessage())];
            $data = $this->serializer->dataSerialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: [];

        if (!$limit || $limit > 100) {
            $limit = 100;
        }
        if ($page && $page > 0) {
            $limit = $limit ?: 10;
            $skip = $limit * ($page - 1);
        } else {
            $page = null;
        }

        $qb = $seriesRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
            ->skip($skip)
            ->sort($sort)
        ;

        $total = $qb->count()->getQuery()->execute();
        $series = $qb_series->getQuery()->execute();

        $counts = [
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'criteria' => $criteria,
            'sort' => $sort,
            'series' => $series,
        ];

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/series/user.{_format}", defaults={"_format"="json"}, requirements={"_format": "json|xml"})
     */
    public function userSeriesAction(Request $request)
    {
        $userRepo = $this->documentManager->getRepository(User::class);
        $sort = $request->get('sort') ?: [];
        $onlyAdminSeries = $request->get('adminSeries') ?: false;
        $limit = (int) $request->get('limit');

        $personalScopeRoleCode = $this->personService->getPersonalScopeRoleCode();

        try {
            $criteria = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Invalid criteria (%s)', $e->getMessage())];
            $data = $this->serializer->dataSerialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        if (!isset($criteria['owner'])) {
            return new Response('');
        }
        $user = $userRepo->createQueryBuilder()->field('username')->equals($criteria['owner'])->getQuery()->getSingleResult();

        $seriesOfUser = $this->seriesService->getSeriesOfUser($user, $onlyAdminSeries, $personalScopeRoleCode, $sort, $limit);

        $seriesOfUser = [
            'total' => is_countable($seriesOfUser) ? count($seriesOfUser) : 0,
            'limit' => $limit,
            'sort' => $sort,
            'criteria' => $criteria,
            'seriesOfUser' => $seriesOfUser->toArray(),
        ];

        $data = $this->serializer->dataSerialize($seriesOfUser, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/live.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function liveAction(Request $request)
    {
        $liveRepo = $this->documentManager->getRepository(Live::class);

        $limit = $request->get('limit');
        if (!$limit || $limit > 100) {
            $limit = 100;
        }

        try {
            $criteria = $this->getCriteria($request->get('criteria'), $request->get('criteriajson'));
        } catch (\Exception $e) {
            $error = ['error' => sprintf('Invalid criteria (%s)', $e->getMessage())];
            $data = $this->serializer->dataSerialize($error, $request->getRequestFormat());

            return new Response($data, 400);
        }

        $sort = $request->get('sort') ?: [];

        $qb = $liveRepo->createQueryBuilder();

        if ($criteria) {
            $qb = $qb->addAnd($criteria);
        }

        $qb_series = clone $qb;
        $qb_series = $qb_series->limit($limit)
            ->sort($sort)
        ;

        $qb_live = clone $qb;

        $total = $qb->count()->getQuery()->execute();
        $live = $qb_live->getQuery()->execute();

        $counts = [
            'total' => $total,
            'limit' => $limit,
            'criteria' => $criteria,
            'sort' => $sort,
            'live' => $live,
        ];

        $data = $this->serializer->dataSerialize($counts, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * @Route("/locales.{_format}", defaults={"_format"="json"}, requirements={"_format"="json|xml"})
     */
    public function localesAction(Request $request, array $locales)
    {
        $data = $this->serializer->dataSerialize($locales, $request->getRequestFormat());

        return new Response($data);
    }

    /**
     * Custom case for multimediaobject croteria. For Backward Compatibility (BC).
     *
     * @see APIController::getCriteria
     *
     * @param mixed $row
     * @param mixed $json
     */
    private function getMultimediaObjectCriteria($row, $json)
    {
        if ($json) {
            $criteria = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
        } else {
            $criteria = (array) $row;

            if (isset($criteria['status'])) {
                if (is_array($criteria['status'])) {
                    $newStatus = [];
                    foreach ($criteria['status'] as $k => $v) {
                        $newStatus[$k] = array_map('intval', (array) $v);
                    }
                    $criteria['status'] = $newStatus;
                } else {
                    $criteria['status'] = (int) $criteria['status'];
                }
            }
        }

        return $criteria;
    }

    /**
     * Get criteria to filter objects from the requets.
     *
     * JSON criteria has priority over row criteria.
     *
     * @param array|string $row
     * @param string       $json
     *
     * @return array
     */
    private function getCriteria($row, $json)
    {
        if ($json) {
            $criteria = json_decode($json, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }
        } else {
            $criteria = (array) $row;
        }

        return $criteria;
    }
}
