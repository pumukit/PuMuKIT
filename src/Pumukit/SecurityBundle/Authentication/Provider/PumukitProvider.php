<?php

namespace Pumukit\SecurityBundle\Authentication\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Document\User;

class PumukitProvider implements AuthenticationProviderInterface
{
    const CAS_MAIL_KEY = 'MAIL';

    private $userProvider;
    private $providerKey;
    private $userChecker;
    private $container;
    private $createUsers;

    public function __construct(UserProviderInterface $userProvider, $providerKey, UserCheckerInterface $userChecker, ContainerInterface $container, $createUsers = true)
    {
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
        $this->userChecker = $userChecker;
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
        $this->createUsers = $createUsers;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        if (!$user = $token->getUser()) {
            throw new BadCredentialsException('No pre-authenticated principal found in request.');
        }

        try {
            $user = $this->userProvider->loadUserByUsername($user);
        } catch (UsernameNotFoundException $notFound) {
            if ($this->createUsers) {
                $user = $this->createUser($user);
            } else {
                throw new BadCredentialsException('Bad credentials', 0, $notFound);
            }
        } catch (\Exception $repositoryProblem) {
            $ex = new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
            $ex->setToken($token);
            throw $ex;
        }

        $this->userChecker->checkPreAuth($user);
        $this->updateUser($user);
        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new PreAuthenticatedToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    private function createUser($userName)
    {
        $userService = $this->container->get('pumukitschema.user');
        $personService = $this->container->get('pumukitschema.person');
        $permissionProfileService = $this->container->get('pumukitschema.permissionprofile');
        $casService = $this->container->get('pumukit.casservice');

        $casService->forceAuthentication();
        $attributes = $casService->getAttributes();

        if ($userService && $personService) {
            //TODO create createDefaultUser in UserService.
            //$this->userService->createDefaultUser($user);
            $user = new User();
            $user->setUsername($userName);

            if (isset($attributes[self::CAS_MAIL_KEY])) {
                $user->setEmail($attributes[self::CAS_MAIL_KEY]);
            }

            $defaultPermissionProfile = $permissionProfileService->getDefault();
            if (null == $defaultPermissionProfile) {
                throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
            }
            $user->setPermissionProfile($defaultPermissionProfile);
            $user->setOrigin('cas');
            $user->setEnabled(true);

            $userService->create($user);
            $personService->referencePersonIntoUser($user);

            return $user;
        }

        throw new AuthenticationServiceException('Not UserService to create a new user');
    }


    private function updateUser(User $user)
    {
        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $casService = $this->container->get('pumukit.casservice');

        $casService->forceAuthentication();
        $attributes = $casService->getAttributes();

        if ((isset($attributes[self::CAS_MAIL_KEY])) && ($attributes[self::CAS_MAIL_KEY] !== $user->getEmail())) {
            $user->setEmail($attributes[self::CAS_MAIL_KEY]);
            $dm->persist($object);
            $dm->flush();
        }

    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof PreAuthenticatedToken;
    }
}
