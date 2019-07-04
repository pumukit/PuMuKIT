<?php

namespace Pumukit\CasBundle\Authentication\Provider;

use Pumukit\CasBundle\Services\CASUserService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class PumukitProvider.
 */
class PumukitProvider implements AuthenticationProviderInterface
{
    private $userProvider;
    private $providerKey;
    private $userChecker;
    private $container;
    private $createUsers;
    private $CASUserService;

    public function __construct(UserProviderInterface $userProvider, $providerKey, UserCheckerInterface $userChecker, ContainerInterface $container, CASUserService $CASUserService, $createUsers = true)
    {
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
        $this->userChecker = $userChecker;
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
        $this->createUsers = $createUsers;
        $this->CASUserService = $CASUserService;
    }

    /**
     * @param TokenInterface $token
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return false;
        }

        if (!$user = $token->getUser()) {
            throw new BadCredentialsException('No pre-authenticated principal found in request.');
        }

        try {
            $user = $this->userProvider->loadUserByUsername($user);
        } catch (UsernameNotFoundException $notFound) {
            if ($this->createUsers) {
                $user = $this->CASUserService->createDefaultUser($user);
            } else {
                throw new BadCredentialsException('Bad credentials', 0, $notFound);
            }
        } catch (\Exception $repositoryProblem) {
            $ex = new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
            $ex->setToken($token);

            throw $ex;
        }

        $this->userChecker->checkPreAuth($user);
        $this->CASUserService->updateUser($user);
        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new PreAuthenticatedToken(
            $user,
            $token->getCredentials(),
            $this->providerKey,
            $user->getRoles()
        );
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    /**
     * @param TokenInterface $token
     *
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PreAuthenticatedToken;
    }
}
