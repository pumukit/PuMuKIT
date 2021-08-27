<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Translation\TranslatorInterface;

class PersonalSeriesListener
{
    private $documentManager;
    private $factoryService;
    private $authorizationChecker;
    private $translator;
    private $locales;
    private $personalScopeRoleCode;

    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorInterface $translator,
        array $locales,
        string $personalScopeRoleCode
    ) {
        $this->documentManager = $documentManager;
        $this->factoryService = $factoryService;
        $this->authorizationChecker = $authorizationChecker;
        $this->translator = $translator;
        $this->locales = $locales;
        $this->personalScopeRoleCode = $personalScopeRoleCode;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($this->hasUserAutoCreateSeriesRole()) {
            if (!$user->getPersonalSeries() || !$this->seriesExists($user, $user->getPersonalSeries())) {
                $series = $this->factoryService->createSeries($user, $this->generateDefaultTitle($user));
                $series->setProperty('personal_series', true);
                $user->setPersonalSeries($series->getId());
                $this->documentManager->flush();
            }
        }
    }

    public function hasUserAutoCreateSeriesRole(): bool
    {
        return $this->authorizationChecker->isGranted(Permission::AUTO_CREATE_PERSONAL_SERIES);
    }

    public function seriesExists(UserInterface $user, ?string $series): bool
    {
        if (!$series) {
            return false;
        }

        $series = $this->documentManager->getRepository(Series::class)->findOneBy(['_id' => $series]);
        if ($series instanceof Series) {
            if ($this->isOwnerOnSeriesPrototype($user, $series)) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function generateDefaultTitle(UserInterface $user): array
    {
        $i18nTitle = [];
        foreach ($this->locales as $locale) {
            $i18nTitle[$locale] = $this->translator->trans('Videos of ').$user->getUsername();
        }

        return $i18nTitle;
    }

    public function isOwnerOnSeriesPrototype(UserInterface $user, Series $series): bool
    {
        $prototype = $this->documentManager->getRepository(MultimediaObject::class)->findPrototype($series);
        if ($prototype instanceof MultimediaObject) {
            if (!$user->getPerson()) {
                return false;
            }

            $role = $this->documentManager->getRepository(Role::class)->findOneBy(['cod' => $this->personalScopeRoleCode]);
            if ($prototype->containsPersonWithRole($user->getPerson(), $role)) {
                return true;
            }
        }

        return false;
    }
}
