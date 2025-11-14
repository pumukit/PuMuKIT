<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\SchemaBundle\Document\User;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'pumukit_login';
    private const EXCEPTION_MESSAGE = 'Invalid login';

    private $objectManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $recaptcha;
    private $logger;
    private $recaptchaEnabled;

    public function __construct(DocumentManager $objectManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, ?ReCaptcha $recaptcha, LoggerInterface $logger, bool $recaptchaEnabled = false)
    {
        $this->objectManager = $objectManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->recaptcha = $recaptcha;
        $this->logger = $logger;
        $this->recaptchaEnabled = $recaptchaEnabled;
    }

    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route') && $request->isMethod('POST');
    }

    public function getCredentials(Request $request): array
    {
        // Honeypot field
        if ($request->request->get('website')) {
            throw new CustomUserMessageAuthenticationException('Bot detected.');
        }

        // Rate limiting
        $attempts = $request->getSession()->get('login_attempts', 0);
        $lastAttempt = $request->getSession()->get('login_last_attempt', 0);
        $now = time();

        if ($now - $lastAttempt > 60) {
            $attempts = 0; // reset after 1 minute
        }

        ++$attempts;
        $request->getSession()->set('login_attempts', $attempts);
        $request->getSession()->set('login_last_attempt', $now);

        if ($attempts > 5) {
            throw new CustomUserMessageAuthenticationException('Too many attempts. Please try again later.');
        }

        if ($this->recaptchaEnabled && $this->recaptcha) {
            $captchaResponse = $request->request->get('g-recaptcha-response');
            $result = $this->recaptcha->verify($captchaResponse, $request->getClientIp());

            $this->logger->info('reCAPTCHA verification', [
                'success' => $result->isSuccess(),
                'errors' => $result->getErrorCodes(),
                'payload' => method_exists($result, 'getResult') ? $result->getResult() : null,
            ]);

            if (!$result->isSuccess()) {
                throw new CustomUserMessageAuthenticationException(
                    'reCAPTCHA inválido: '.implode(', ', $result->getErrorCodes())
                );
            }

            if (method_exists($result, 'getResult')) {
                $payload = $result->getResult();
                $score = $payload['score'] ?? 1;
                $action = $payload['action'] ?? 'login';
                if ('login' !== $action || $score < 0.5) {
                    throw new CustomUserMessageAuthenticationException(
                        sprintf('reCAPTCHA sospechoso (acción: %s, score: %.2f)', $action, $score)
                    );
                }
            }
        }

        $credentials = [
            'username' => strtolower($request->request->get('username')),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->objectManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Username could not be found.');
        }

        if (!$user->isEnabled() && !$user->isResetLoginAttemptsAllowed()) {
            throw new CustomUserMessageAuthenticationException('User deactivated, please wait at least '.User::RESET_LOGIN_ATTEMPTS_INTERVAL.' to retry');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param mixed $credentials
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $user->resetLoginAttempts();
            $this->objectManager->flush();
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $message = $exception->getMessage();

        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $request->getSession()->getFlashBag()->add('error', $message);

            return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
        }

        $username = $request->request->get('username');
        if (!$username) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $user = $this->objectManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            throw new UsernameNotFoundException(self::EXCEPTION_MESSAGE);
        }

        $this->updateUser($user);

        if ($user->canLogin()) {
            $request->getSession()->getFlashBag()->add('error', 'Username or password invalid');
        } else {
            $request->getSession()->getFlashBag()->add('error_max_attempt', 'Please, wait '.User::RESET_LOGIN_ATTEMPTS_INTERVAL.' before trying again.');
        }

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
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
