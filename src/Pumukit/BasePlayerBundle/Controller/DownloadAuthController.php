<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Controller;

use Psr\Log\LoggerInterface;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Internal endpoint used by the nginx auth_request directive on the download vhost.
 * Validates HMAC-SHA256 tokens before nginx serves any file from the downloads directory.
 * This prevents unauthenticated access to media files even when MongoDB ObjectIds are known.
 *
 * nginx calls GET /download/validate-token?token=XXX&expires=YYY&resource=ZZZ
 * Returns 200 (allow) or 401 (deny).
 */
class DownloadAuthController extends AbstractController
{
    private SecureTokenService $secureTokenService;
    private LoggerInterface $logger;

    public function __construct(SecureTokenService $secureTokenService, LoggerInterface $logger)
    {
        $this->secureTokenService = $secureTokenService;
        $this->logger = $logger;
    }

    /**
     * @Route("/download/validate-token", name="pumukit_download_validate_token", methods={"GET"})
     */
    public function validateToken(Request $request): Response
    {
        $token = $request->query->get('token', '');
        $expires = $request->query->getInt('expires', 0);
        $resource = $request->query->get('resource', '');
        $clientIp = $request->getClientIp();
        $originalUri = $request->headers->get('X-Original-URI', $request->getRequestUri());

        if (!$token || !$expires || !$resource) {
            $this->logger->warning('Download blocked: missing token parameters', [
                'ip' => $clientIp,
                'uri' => $originalUri,
            ]);

            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->secureTokenService->validateToken($token, $resource, $expires)) {
            $this->logger->warning('Download blocked: invalid or expired token', [
                'ip' => $clientIp,
                'resource' => $resource,
                'uri' => $originalUri,
            ]);

            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        return new Response('', Response::HTTP_OK);
    }
}
