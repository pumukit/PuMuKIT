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
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/tus', name: 'tus_post')]
    #[Route('/tus/{token}', name: 'tus_post_token', requirements: ['token' => '.+'])]
    #[Route('/files/{token}', name: 'tus_files', requirements: ['token' => '.+'])]
    #[IsGranted('ROLE_UPLOAD_INBOX')]
    public function server(Request $request, Server $server)
    {
        if ($request->isMethod('DELETE')) {
            throw new AccessDeniedHttpException('File deletion is not allowed.');
        }

        if ($request->isMethod('POST')) {
            $this->validateFileExtension($request);
        }

        $series = $request->query->get('series');

        if (!empty($series)) {
            try {
                $folder = $this->inboxService->folderName($series);

                $basePath = realpath($this->inboxService->inboxPath());
                if (!$basePath) {
                    throw new \Exception('Base upload directory does not exist.');
                }
                $path = $basePath.DIRECTORY_SEPARATOR.$folder;

                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                if ($request->isMethod('POST')) {
                    $filename = $this->uploadFileName($this->uploadMetadata($request));
                    if ('' !== $filename) {
                        $targetFile = $path.DIRECTORY_SEPARATOR.$filename;

                        if (file_exists($targetFile)) {
                            $this->logger->warning('TUS ERROR: File already exists: '.$targetFile);

                            throw new ConflictHttpException("A file with the name '{$filename}' already exists in this folder.");
                        }
                    }

                    if ($request->query->getBoolean('temporaryFolder')) {
                        touch($path.DIRECTORY_SEPARATOR.'.pumukit-temporary');
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

    private function validateFileExtension(Request $request): void
    {
        $metadata = $this->uploadMetadata($request);
        $filename = $this->uploadFileName($metadata);
        if ('' === $filename) {
            throw new BadRequestHttpException('Missing file metadata.');
        }

        $declaredMimeType = $this->consistentMetadata($metadata, 'type', 'filetype');
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

    private function uploadMetadata(Request $request): array
    {
        $metadata = [];
        foreach (explode(',', (string) $request->headers->get('Upload-Metadata')) as $chunk) {
            $pieces = explode(' ', trim($chunk));
            if ('' === $pieces[0]) {
                continue;
            }
            if (array_key_exists($pieces[0], $metadata)) {
                throw new BadRequestHttpException('Inconsistent file metadata.');
            }
            $metadata[$pieces[0]] = base64_decode($pieces[1] ?? '');
        }

        return $metadata;
    }

    private function uploadFileName(array $metadata): string
    {
        return $this->consistentMetadata($metadata, 'name', 'filename');
    }

    private function consistentMetadata(array $metadata, string $key, string $alias): string
    {
        $value = (string) ($metadata[$key] ?? '');
        $aliasValue = (string) ($metadata[$alias] ?? '');
        if ('' !== $value && '' !== $aliasValue && $value !== $aliasValue) {
            throw new BadRequestHttpException('Inconsistent file metadata.');
        }

        return '' !== $value ? $value : $aliasValue;
    }
}
