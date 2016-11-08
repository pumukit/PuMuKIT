<?php

namespace Pumukit\EncoderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin/encoder")
 * @Security("is_granted('ROLE_ACCESS_JOBS')")
 */
class CPUsController extends Controller
{
    /**
     * @Route("/cpu/maintenance", name="pumukit_encoder_cpu_maintenance", requirements={"activateMaintenance": "activate|deactivate"})
     */
    public function maintenanceAction(Request $request)
    {
        $cpuName = $request->get('cpu_name');
        if (!$cpuName) {
            throw $this->createNotFoundException("There is no required 'cpu_name' parameter");
        }
        $activateMaintenance = null;
        $activate = $request->get('activate');
        $deactivate = $request->get('deactivate');
        if (($activate !== null && ($activate == true || $activate === 'activate' || $activate === 'true' || $activate === ''))
            || ($deactivate != null && ($deactivate == false || $deactivate === 'false'))) {
            $activateMaintenance = 'activate';
        } elseif (($deactivate !== null && ($deactivate == true || $deactivate === 'deactivate' || $deactivate === 'true' || $deactivate === ''))
                || ($activate != null && ($activate == false || $activate === 'false'))) {
            $activateMaintenance = 'deactivate';
        }
        if (!$activateMaintenance) {
            throw $this->createNotFoundException("There is no required 'activate' or 'deactivate' parameter");
        }

        return $this->forward('PumukitEncoderBundle:CPUs:switchMaintenance', [
            'activateMaintenance' => $activateMaintenance,
            'cpuName' => $cpuName,
        ]);
    }

    /**
     * @Route("/cpu/maintenance/{activateMaintenance}/{cpuName}", name="pumukit_encoder_cpu_maintenance_switch", requirements={"activateMaintenance": "activate|deactivate"})
     */
    public function switchMaintenanceAction(Request $request, $activateMaintenance, $cpuName)
    {
        $cpuService = $this->get('pumukitencoder.cpu');
        $jobService = $this->get('pumukitencoder.job');
        $cpu = $cpuService->getCpuByName($cpuName);
        if (!$cpu) {
            throw $this->createNotFoundException("The CPU with the name $cpuName does not exist");
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
