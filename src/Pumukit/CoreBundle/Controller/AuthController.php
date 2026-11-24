<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController implements WebTVControllerInterface
{
    #[Route('/auth', name: 'pumukit_auth')]
    public function changeAction(Request $request): RedirectResponse
    {
        if (!$request->getSession()->has('target_path')) {
            $referer = $request->headers->get('referer', '/');
            $request->getSession()->set('target_path', $request->query->get('referer', $referer));
        }

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException('Unable to access this page!');
        }

        $targetUrl = (string) $request->getSession()->get('target_path');
        $request->getSession()->remove('target_path');

        return $this->redirect($this->isWithinTheSite($request, $targetUrl) ? $targetUrl : '/');
    }

    private function isWithinTheSite(Request $request, string $url): bool
    {
        if ('' === $url || preg_match('/[\x00-\x1f\x7f\\\]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && 0 === strcasecmp($parts['host'] ?? '', $request->getHost());
    }
}
