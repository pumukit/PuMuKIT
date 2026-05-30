<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\StatsService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ACCESS_DASHBOARD')]
class DashboardController extends AbstractController implements NewAdminControllerInterface
{
    private const RECENT_SERIES_LIMIT = 5;
    private const ALLOWED_GROUP_BY = ['day', 'month', 'year'];

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly StatsService $statsService,
        private readonly ProfileService $profileService,
    ) {
    }

    /**
     * @Route("/dashboard")
     * @Route("/dashboard/default", name="pumukit_newadmin_dashboard_index_default")
     */
    #[Template('@PumukitNewAdmin/Dashboard/index.html.twig')]
    public function indexAction(Request $request): array
    {
        $groupBy = $request->get('group_by', 'month');
        if (!in_array($groupBy, self::ALLOWED_GROUP_BY, true)) {
            $groupBy = 'month';
        }

        $isGlobal = $this->isGranted(PermissionProfile::SCOPE_GLOBAL) || $this->isGranted('ROLE_SUPER_ADMIN');
        $user = $this->getUser();
        $ownerId = (!$isGlobal && $user instanceof User) ? $user->getId() : null;

        $stats = $this->statsService->getGlobalStats($groupBy, 1, $ownerId);
        $activityFrom = $this->statsService->getActivityWindowStart($groupBy);
        $activityTo = (new \DateTimeImmutable('today'))->modify('+1 day');

        return [
            'is_global' => $isGlobal,
            'group_by' => $groupBy,
            'stats' => $stats,
            'activity' => $this->statsService->getMmobjActivityByDayHour($groupBy, $ownerId),
            'activity_window' => [
                'from' => $activityFrom->format('c'),
                'to' => $activityTo->format('c'),
            ],
            'storage' => $isGlobal ? $this->profileService->getDirOutInfo() : [],
            'num_series' => $this->countSeries($ownerId),
            'num_mm' => array_sum(array_map(static fn ($e) => $e['num'], $stats)),
            'duration' => array_sum(array_map(static fn ($e) => $e['duration'], $stats)),
            'size' => array_sum(array_map(static fn ($e) => $e['size'], $stats)),
            'recent_series' => $this->buildRecentSeries($ownerId),
        ];
    }

    private function countSeries(?string $ownerId): int
    {
        $repo = $this->documentManager->getRepository(Series::class);
        if (!$ownerId) {
            return $repo->count();
        }

        return (int) $repo->createQueryBuilder()
            ->field('properties.owners')->equals($ownerId)
            ->count()
            ->getQuery()
            ->execute();
    }

    private function buildRecentSeries(?string $ownerId): array
    {
        $repo = $this->documentManager->getRepository(Series::class);
        $criteria = $ownerId ? ['properties.owners' => $ownerId] : [];
        $series = $repo->findBy($criteria, ['id' => 'desc'], self::RECENT_SERIES_LIMIT);
        if (!$series) {
            return [];
        }

        $userRepo = $this->documentManager->getRepository(User::class);
        $out = [];
        foreach ($series as $s) {
            $owners = $s->getProperty('owners') ?? [];
            $ownerName = null;
            if ($owners) {
                $owner = $userRepo->find($owners[0]);
                $ownerName = $owner?->getUsername();
            }
            $out[] = [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'publicDate' => $s->getPublicDate(),
                'owner' => $ownerName,
            ];
        }

        return $out;
    }
}
