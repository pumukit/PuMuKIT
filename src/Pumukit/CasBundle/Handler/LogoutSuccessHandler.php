<?php

namespace Pumukit\CasBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Pumukit\CasBundle\Services\CASService;

/**
 * Class LogoutSuccessHandler.
 */
class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private $options;
    private $router;
    protected $casService;

    /**
     * LogoutSuccessHandler constructor.
     *
     * @param array                 $options
     * @param UrlGeneratorInterface $router
     * @param CASService            $casService
     */
    public function __construct(array $options, UrlGeneratorInterface $router, CASService $casService)
    {
        $this->options = $options;
        $this->router = $router;
        $this->casService = $casService;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    public function onLogoutSuccess(Request $request)
    {
        $url = $this->router->generate('pumukit_webtv_index_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->casService->logoutWithRedirectService($url);

        /* Call CAS API to do authentication */
        /*
        \phpCAS::client($this->options['cas_protocol'], $this->options['cas_server'], $this->options['cas_port'], $this->options['cas_path'], false);
        if (!isset($this->options['cas_logout']) || empty($this->options['cas_logout'])) {
          \phpCAS::logout();
        } else {
          // generate absolute URL
          $url = $this->router->generate($this->options['cas_logout'], array(), UrlGeneratorInterface::ABSOLUTE_URL);
          \phpCAS::logoutWithRedirectService($url);
        }
        return null;
        */
    }
}
