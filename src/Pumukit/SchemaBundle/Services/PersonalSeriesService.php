<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PersonalSeriesService
{
    public const DEFAULT_PERSONAL_SERIES_PROPERTY = 'personal_series';

    private $documentManager;
    private $factoryService;
    private $authorizationChecker;
    private $tokenStorage;
    private $translator;
    private $locales;
    private $personalScopeRoleCode;

    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        \Symfony\Contracts\Translation\TranslatorInterface $translator,
        array $locales,
        string $personalScopeRoleCode
    ) {
        $this->documentManager = $documentManager;
        $this->factoryService = $factoryService;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->locales = $locales;
        $this->personalScopeRoleCode = $personalScopeRoleCode;
    }

    public function find(): ?Series
    {
        if (!$this->hasUserAutoCreateSeriesRole()) {
            return null;
        }

        if (!$this->isValidPersonalSeries()) {
            return $this->create();
        }

        return $this->getSeries($this->getLoggedUser());
    }

    public function create(): Series
    {
        $user = $this->getLoggedUser();
        $series = $this->factoryService->createSeries($user, $this->generateDefaultPersonalSeriesTitle());
        $series->setProperty(self::DEFAULT_PERSONAL_SERIES_PROPERTY, true);
        $user->setPersonalSeries($series->getId());

        $this->documentManager->flush();

        return $series;
    }

    private function getLoggedUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    private function hasUserAutoCreateSeriesRole(): bool
    {
        return $this->authorizationChecker->isGranted(Permission::AUTO_CREATE_PERSONAL_SERIES);
    }

    private function generateDefaultPersonalSeriesTitle(): array
    {
        $i18nTitle = [];
        foreach ($this->locales as $locale) {
            $i18nTitle[$locale] = $this->translator->trans('Videos of ').$this->getLoggedUser()->getUsername();
        }

        return $i18nTitle;
    }

    private function isValidPersonalSeries(): bool
    {
        $user = $this->getLoggedUser();

        if (!$user->getPersonalSeries()) {
            return false;
        }

        $series = $this->getSeries($user);

        if (!$series instanceof Series) {
            return false;
        }

        if (!$this->isOwnerOfAssociatedSeries($series)) {
            return false;
        }

        return true;
    }

    private function isOwnerOfAssociatedSeries(Series $series): bool
    {
        $user = $this->getLoggedUser();
        $prototype = $this->documentManager->getRepository(MultimediaObject::class)->findPrototype($series);
        if ($prototype instanceof MultimediaObject) {
            if (!$user->getPerson()) {
                return false;
            }

            $role = $this->documentManager->getRepository(Role::class)->findOneBy([
                'cod' => $this->personalScopeRoleCode,
            ]);

            if ($prototype->containsPersonWithRole($user->getPerson(), $role)) {
                return true;
            }
        }

        return false;
    }

    private function getSeries(User $user)
    {
        return $this->documentManager->getRepository(Series::class)->findOneBy([
            '_id' => new ObjectId($user->getPersonalSeries()),
        ]);
    }
}
