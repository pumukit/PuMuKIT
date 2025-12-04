<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\Controller;

use App\Transcoding\Application\Cpu\ToggleMaintenance\ToggleMaintenanceRequest;
use App\Transcoding\Application\Cpu\ToggleMaintenance\ToggleMaintenanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ToggleCpuMaintenanceController extends AbstractController
{
    public function __construct(private readonly ToggleMaintenanceService $service) {}

    public function __invoke(string $cpuName, string $action): Response
    {
        $activate = 'activate' === $action;

        $request = new ToggleMaintenanceRequest(
            cpuName: $cpuName,
            activate: $activate
        );

        $response = ($this->service)($request);

        return new JsonResponse([
            'success' => true,
            'cpu' => $response->cpuName,
            'in_maintenance' => $response->inMaintenance,
        ]);
    }
}
