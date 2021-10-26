<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Controller;

use Pumukit\EncoderBundle\Services\CpuService;
use Pumukit\EncoderBundle\Services\JobService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/encoder")
 * @Security("is_granted('ROLE_ACCESS_JOBS')")
 */
class CPUsController extends AbstractController
{
    /**
     * @Route("/cpu/maintenance", name="pumukit_encoder_cpu_maintenance", requirements={"activateMaintenance": "activate|deactivate"})
     */
    public function maintenanceAction(Request $request): Response
    {
        $cpuName = $request->get('cpu_name');
        if (!$cpuName) {
            throw $this->createNotFoundException("There is no required 'cpu_name' parameter");
        }
        $activateMaintenance = null;
        $activate = $request->get('activate');
        $deactivate = $request->get('deactivate');
        if ((null !== $activate && (true === $activate || 'activate' === $activate || 'true' === $activate || '' === $activate))
            || (null !== $deactivate && (false === $deactivate || 'false' === $deactivate))) {
            $activateMaintenance = 'activate';
        } elseif ((null !== $deactivate && (true === $deactivate || 'deactivate' === $deactivate || 'true' === $deactivate || '' === $deactivate))
                || (null !== $activate && (false === $activate || 'false' === $activate))) {
            $activateMaintenance = 'deactivate';
        }
        if (!$activateMaintenance) {
            throw $this->createNotFoundException("There is no required 'activate' or 'deactivate' parameter");
        }

        return $this->forward('@PumukitEncoder/CPUs/switchMaintenance', [
            'activateMaintenance' => $activateMaintenance,
            'cpuName' => $cpuName,
        ]);
    }

    /**
     * @Route("/cpu/maintenance/{activateMaintenance}/{cpuName}", name="pumukit_encoder_cpu_maintenance_switch", requirements={"activateMaintenance": "activate|deactivate"})
     */
    public function switchMaintenanceAction(Request $request, CpuService $cpuService, JobService $jobService, string $activateMaintenance, string $cpuName): Response
    {
        $cpu = $cpuService->getCpuByName($cpuName);
        if (!$cpu) {
            throw $this->createNotFoundException("The CPU with the name {$cpuName} does not exist");
        }

        switch ($activateMaintenance) {
        case 'activate':
            $cpuService->activateMaintenance($cpuName);

            break;

        case 'deactivate':
            $cpuService->deactivateMaintenance($cpuName);
            for ($i = 0; $i < $cpu['max']; ++$i) {
                $jobService->executeNextJob();
            }

            break;
        }

        return new Response();
    }
}
