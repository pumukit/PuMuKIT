<?php

namespace Pumukit\SecurityBundle\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Util\StringUtils;

class PumukitProvider implements AuthenticationProviderInterface
{
  private $userProvider;
  private $providerKey;

  public function __construct(UserProviderInterface $userProvider, $providerKey)
  {
    $this->userProvider = $userProvider;
    $this->providerKey = $providerKey;
  }

  public function authenticate(TokenInterface $token)
  {
    if (!$this->supports($token)) {
      return;
    }

    if (!$user = $token->getUser()) {
      throw new BadCredentialsException('No pre-authenticated principal found in request.');
    }

    try{
    $user = $this->userProvider->loadUserByUsername($user);
    }catch(\Exception $e){
      var_dump($e);
      var_dump($user); exit;
    }

    //TODO
    //$this->userChecker->checkPostAuth($user);

    $authenticatedToken = new PreAuthenticatedToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
    $authenticatedToken->setAttributes($token->getAttributes());

    return $authenticatedToken;


    /*
    if ($user) {
      $authenticatedToken = new PreAuthenticatedToken($user, "XXX", "main", $user->getRoles());
      $authenticatedToken->setUser($user);

      return $authenticatedToken;
    }

    throw new AuthenticationException('The pumukit authentication failed.');
    */
  }

  public function supports(TokenInterface $token)
  {
    return $token instanceof PreAuthenticatedToken;
  }
}