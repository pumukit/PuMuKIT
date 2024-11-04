<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class UNESCOSearchExporterController extends AbstractController
{
    /**
     * @Route("/export/unesco-search/start", name="trigger_export_unesco_search")
     */
    public function triggerExportCommand(): JsonResponse
    {
        $process = new Process(['php', 'bin/console', 'pumukit:export-unesco-csv']);
        $process->setTimeout(3600);

        try {
            $process->start();

            return new JsonResponse(['status' => 'success', 'message' => 'Export started. You will receive an email when it is ready.']);
        } catch (ProcessFailedException $exception) {
            return new JsonResponse(['status' => 'error', 'message' => 'Failed to start the export.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/download/unesco/temp-file/{filename}", name="download_unesco_search")
     */
    public function downloadUNESCOSearch(string $filename)
    {
        $filePath = sys_get_temp_dir().'/'.$filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->deleteFileAfterSend(true);

        return $response;
    }
}
