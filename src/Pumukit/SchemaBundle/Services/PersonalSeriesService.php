<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class PersonalSeriesService
{
    public const DEFAULT_PERSONAL_SERIES_PROPERTY = 'personal_series';

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly FactoryService $factoryService,
        private readonly TranslatorInterface $translator,
        private readonly array $locales,
        private readonly string $personalScopeRoleCode,
    ) {}

    public function find(User $user): ?Series
    {
        $series = $this->series((string) $user->getPersonalSeries());
        if (!$series instanceof Series || !$this->isOwner($user, $series)) {
            return null;
        }

        return $series;
    }

    public function getOrCreate(User $user): Series
    {
        $series = $this->find($user);
        if ($series instanceof Series) {
            return $series;
        }

        $previous = $user->getPersonalSeries();
        $series = $this->factoryService->createSeries($user, $this->title($user));
        $series->setProperty(self::DEFAULT_PERSONAL_SERIES_PROPERTY, true);
        $this->documentManager->flush();

        $result = $this->documentManager->getDocumentCollection(User::class)->updateOne(
            ['_id' => new ObjectId($user->getId()), 'personalSeries' => $previous],
            ['$set' => ['personalSeries' => $series->getId()]]
        );

        if (0 === $result->getMatchedCount()) {
            $this->factoryService->deleteSeries($series);
            $this->documentManager->refresh($user);
            $winner = $this->find($user);
            if ($winner instanceof Series) {
                return $winner;
            }

            throw new \RuntimeException(sprintf('The personal series of the user "%s" could not be created.', $user->getUsername()));
        }

        $user->setPersonalSeries($series->getId());

        return $series;
    }

    public function owners(MultimediaObject $multimediaObject): array
    {
        return $multimediaObject->getPeopleByRoleCod($this->personalScopeRoleCode, true);
    }

    public function userOf(string $personId): ?User
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $personId)) {
            return null;
        }

        $person = $this->documentManager->getRepository(Person::class)->find(new ObjectId($personId));
        if ($person instanceof Person && $person->getUser() instanceof User) {
            return $person->getUser();
        }

        $user = $this->documentManager->getRepository(User::class)->findOneBy(['person' => new ObjectId($personId)]);

        return $user instanceof User ? $user : null;
    }

    private function isOwner(User $user, Series $series): bool
    {
        $prototype = $this->documentManager->getRepository(MultimediaObject::class)->findPrototype($series);
        if (!$prototype instanceof MultimediaObject) {
            return false;
        }

        $owners = $this->owners($prototype);
        if ([] === $owners) {
            return true;
        }

        $person = $user->getPerson();
        if (!$person instanceof Person) {
            return false;
        }

        foreach ($owners as $owner) {
            if ((string) $owner->getId() === (string) $person->getId()) {
                return true;
            }
        }

        return false;
    }

    private function series(string $id): ?Series
    {
        if (!preg_match('/^[a-f\d]{24}$/i', $id)) {
            return null;
        }

        $series = $this->documentManager->getRepository(Series::class)->find(new ObjectId($id));

        return $series instanceof Series ? $series : null;
    }

    private function title(User $user): array
    {
        $name = trim((string) $user->getFullName());
        if ('' === $name) {
            $name = (string) $user->getUsername();
        }

        $title = [];
        foreach ($this->locales as $locale) {
            $title[$locale] = $this->translator->trans('Videos of ', [], null, $locale).$name;
        }

        return $title;
    }
}
