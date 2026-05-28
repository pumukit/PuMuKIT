<?php

namespace Pumukit\CoreBundle\Controller;

use Psr\Log\LoggerInterface;
use Pumukit\CoreBundle\Services\InboxService;
use Pumukit\CoreBundle\Utils\BlackListExtensions;
use Pumukit\CoreBundle\Utils\MediaMimeTypeUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use TusPhp\Middleware\Cors;
use TusPhp\Tus\Server;

class TUSUploadController extends AbstractController
{
    private $inboxService;
    private $logger;

    public function __construct(InboxService $inboxService, LoggerInterface $logger)
    {
        $this->inboxService = $inboxService;
        $this->logger = $logger;
    }

    /**
     * @Route("/tus", name="tus_post")
     * @Route("/tus/{token}", name="tus_post_token", requirements={"token"=".+"})
     * @Route("/files/{token}", name="tus_files", requirements={"token"=".+"})
     */
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    public function server(Request $request, Server $server)
    {
        if ($request->isMethod('DELETE')) {
            throw new AccessDeniedHttpException('File deletion is not allowed.');
        }

        if ($request->isMethod('POST')) {
            $this->validateFileExtension($request);
        }

        $series = $request->get('series');

        if (!empty($series)) {
            try {
                $folder = $this->sanitizeFolderName($series);

                $basePath = realpath($this->inboxService->inboxPath());
                if (!$basePath) {
                    throw new \Exception('Base upload directory does not exist.');
                }
                $path = $basePath.DIRECTORY_SEPARATOR.$folder;

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                if ($request->isMethod('POST')) {
                    $metadata = $request->headers->get('Upload-Metadata');
                    if ($metadata && preg_match('/filename (?P<name>[^\s,]+)/', $metadata, $matches)) {
                        $filename = base64_decode($matches['name']);
                        $targetFile = $path.DIRECTORY_SEPARATOR.$filename;

                        if (file_exists($targetFile)) {
                            $this->logger->warning('TUS ERROR: File already exists: '.$targetFile);

                            throw new ConflictHttpException("A file with the name '{$filename}' already exists in this folder.");
                        }
                    }
                }

                $server->setUploadDir($path);
            } catch (ConflictHttpException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new BadRequestHttpException('Invalid upload destination: '.$e->getMessage());
            }
        }

        $server->middleware()->skip(Cors::class);

        return $server->serve();
    }

    private function sanitizeFolderName($folder): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_ -]/', '', $folder);

        if (empty($clean)) {
            throw new \Exception('Folder name must contain at least one valid character (letters, numbers, underscores, or dashes).');
        }

        return $clean;
    }

    private function validateFileExtension(Request $request): void
    {
        $metadata = $request->headers->get('Upload-Metadata');
        if (!$metadata) {
            throw new BadRequestHttpException('Missing file metadata.');
        }

        $filename = '';
        $declaredMimeType = '';

        if (preg_match('/filename (?P<name>[^\s,]+)/', $metadata, $matches)) {
            $filename = base64_decode($matches['name']);
        }
        if (preg_match('/filetype (?P<type>[^\s,]+)/', $metadata, $matches)) {
            $declaredMimeType = base64_decode($matches['type']);
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (BlackListExtensions::isBlackListed($extension)) {
            $this->logger->error("TUS ERROR: Blocked malicious extension: {$extension} (File: {$filename})");

            throw new BadRequestHttpException('File type strictly forbidden for security reasons.');
        }

        if (!MediaMimeTypeUtils::isAllowed($declaredMimeType, $extension)) {
            $this->logger->warning("TUS ERROR: Mimetype not allowed: {$filename} ({$declaredMimeType})");

            throw new BadRequestHttpException('File type not allowed by policy.');
        }
    }
}
