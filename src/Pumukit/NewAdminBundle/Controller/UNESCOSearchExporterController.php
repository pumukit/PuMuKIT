<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class UNESCOSearchExporterController extends AbstractController
{
    private $pumukitTmp;
    private $binPath;

    /** @var SessionInterface */
    private $session;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(SessionInterface $session, $pumukitTmp, $binPath, RequestStack $requestStack)
    {
        $this->session = $session;
        $this->pumukitTmp = $pumukitTmp;
        $this->binPath = $binPath;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/export/unesco-search/start", name="trigger_export_unesco_search")
     */
    public function triggerExportCommand(): JsonResponse
    {
        $user = $this->getUser();

        $sessionAttributes = [
            'unesco_tag' => $this->session->get('admin/unesco/tag'),
            'unesco_sort' => $this->session->get('admin/unesco/element_sort'),
            'unesco_type' => $this->session->get('admin/unesco/type'),
            'unesco_text' => $this->session->get('admin/unesco/text'),
            'unesco_criteria' => $this->session->get('UNESCO/criteria'),
            'locale' => $this->requestStack->getMainRequest()->getLocale(),
        ];

        $encodedSessionData = base64_encode(json_encode($sessionAttributes, JSON_THROW_ON_ERROR));

        $command = [
            'php',
            "{$this->binPath}/console",
            'pumukit:export-unesco-csv',
            $user->getEmail(),
            '--sessionData='.$encodedSessionData,
        ];

        $process = new Process($command);
        $process->setTimeout(3600);

        try {
            $process->start();

            $process->wait();

            // Check if the process was successful
            if ($process->isSuccessful()) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Export started. You will receive an email when it is ready.',
                ]);
            }

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Failed to start the export.',
                'errorOutput' => $process->getErrorOutput(),
                'output' => $process->getOutput(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

            return new JsonResponse(['status' => 'success', 'message' => 'Export started. You will receive an email when it is ready.']);
        } catch (ProcessFailedException $exception) {
            return new JsonResponse(['status' => 'error', 'message' => 'Failed to start the export.'.$exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/download/unesco/temp-file/{filename}", name="download_unesco_search")
     */
    public function downloadUNESCOSearch(string $filename)
    {
        $filePath = $this->pumukitTmp.'/'.$filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        $response->deleteFileAfterSend(true);

        return $response;
    }
}
