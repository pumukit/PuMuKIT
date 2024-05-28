<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Controller\AdminControllerInterface;
use Pumukit\CoreBundle\Controller\PersonalControllerInterface;
use Pumukit\CoreBundle\Controller\WebTVControllerInterface;
use Pumukit\CoreBundle\Services\FilterService;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class FilterListener.
 */
class FilterListener
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var FilterService
     */
    private $filterService;

    public function __construct(DocumentManager $documentManager, FilterService $filterService)
    {
        $this->dm = $documentManager;
        $this->filterService = $filterService;
    }

    /**
     * @throws \MongoException
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $canBeActivated = $this->filterService->checkFilterActivation($event);
        if (!$canBeActivated) {
            return;
        }
        [$controller, $routeParams] = $this->filterService->getEventData($event);
        if (current($controller) instanceof AdminControllerInterface) {
            $this->enableAdminFilter();
        } elseif (current($controller) instanceof WebTVControllerInterface) {
            $this->enableWebTVFilter($routeParams);
        } elseif (current($controller) instanceof PersonalControllerInterface) {
            $this->enablePersonalFilter($routeParams);
        }
    }

    /**
     * Enables the 'admin' filter.
     *
     * This filter is used mainly in the "back-office" part of the application.
     *
     * @throws \MongoException
     */
    private function enableAdminFilter(): void
    {
        $loggedInUser = $this->filterService->checkUserActivateFilter();
        if (!$loggedInUser) {
            return;
        }
        if ($this->dm->getFilterCollection()->isEnabled('backoffice')) {
            return;
        }
        $filter = $this->dm->getFilterCollection()->enable('backoffice');
        $this->filterService->setAdminParameters($filter, $loggedInUser);
    }

    /**
     * Enable the "WebTV" filter.
     */
    private function enableWebTVFilter(array $routeParams): void
    {
        if (!$this->dm->getFilterCollection()->isEnabled('frontend')) {
            $filter = $this->dm->getFilterCollection()->enable('frontend');
            $this->filterService->setGenericFilterParameters($filter, $routeParams);
            $this->filterService->setFrontendFilterParameters($filter, $routeParams);
        }
    }

    /**
     * @throws \MongoException
     */
    private function enablePersonalFilter(array $routeParams): void
    {
        $loggedInUser = $this->filterService->checkUserActivateFilter();
        if (!$loggedInUser) {
            return;
        }
        if ($this->dm->getFilterCollection()->isEnabled('personal')) {
            return;
        }
        $filter = $this->dm->getFilterCollection()->enable('personal');
        $this->filterService->setGenericFilterParameters($filter, $routeParams);
        $this->filterService->setPersonalFilterParameters($filter, $loggedInUser);
    }
}
