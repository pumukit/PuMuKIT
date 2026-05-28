<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'pumukit_login';

    private $objectManager;
    private $urlGenerator;
    private $loginIpLimiter;

    public function __construct(
        DocumentManager $objectManager,
        UrlGeneratorInterface $urlGenerator,
        RateLimiterFactory $loginIpLimiter
    ) {
        $this->objectManager = $objectManager;
        $this->urlGenerator = $urlGenerator;
        $this->loginIpLimiter = $loginIpLimiter;
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $limiter = $this->loginIpLimiter->create($request->getClientIp());
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new CustomUserMessageAuthenticationException('Unusual activity detected. For your safety, please wait a few minutes before trying again.');
        }

        $username = (string) ($request->request->get('username') ?? '');
        $password = (string) ($request->request->get('password') ?? '');
        $csrfToken = (string) ($request->request->get('_csrf_token') ?? '');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username, fn (string $userIdentifier) => $this->loadUser($userIdentifier)),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $user->resetLoginAttempts();
            $this->objectManager->flush();
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $request->getSession()->getFlashBag()->add('error', $exception->getMessage());

            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        }

        $username = $request->request->get('username');
        $user = $this->objectManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($user) {
            $this->updateUser($user);
        }

        $request->getSession()->getFlashBag()->add('error', 'Invalid credentials or account temporarily blocked.');

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    protected function loadUser(string $username): User
    {
        $user = $this->objectManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials or account temporarily blocked.');
        }

        if (!$user->isEnabled() && $user->isResetLoginAttemptsAllowed()) {
            $user->resetLoginAttempts();
            $this->objectManager->flush();
        }

        if (!$user->canLogin() || !$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials or account temporarily blocked.');
        }

        return $user;
    }

    private function updateUser(User $user): void
    {
        $user->addLoginAttempt();

        if ($user->isResetLoginAttemptsAllowed()) {
            $user->resetLoginAttempts();
        }

        $this->objectManager->flush();
    }
}
