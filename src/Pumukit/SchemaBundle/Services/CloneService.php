<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\ObjectValue\Immutable;

class CloneService
{
    protected $documentManager;
    protected $translator;
    protected $tagService;
    protected $textIndexService;
    protected $embeddedBroadcastService;
    protected $multimediaObjectEventDispatcherService;
    protected $autoNumericValueService;
    protected $locales;

    public function __construct(
        DocumentManager $documentManager,
        \Symfony\Contracts\Translation\TranslatorInterface $translator,
        TagService $tagService,
        TextIndexService $textIndexService,
        EmbeddedBroadcastService $embeddedBroadcastService,
        MultimediaObjectEventDispatcherService $multimediaObjectEventDispatcherService,
        AutoNumericValueService $autoNumericValueService,
        array $locales
    ) {
        $this->documentManager = $documentManager;
        $this->translator = $translator;
        $this->tagService = $tagService;
        $this->textIndexService = $textIndexService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->multimediaObjectEventDispatcherService = $multimediaObjectEventDispatcherService;
        $this->autoNumericValueService = $autoNumericValueService;
        $this->locales = $locales;
    }

    public function cloneMultimediaObject(
        MultimediaObject $baseMultimediaObject,
        string $addToTitle = ''
    ): MultimediaObject {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setLocale($this->locales[0]);
        $multimediaObject->setSeries($baseMultimediaObject->getSeries());
        $multimediaObject->setType($baseMultimediaObject->getType());

        $this->cloneTitle($multimediaObject, $baseMultimediaObject, $addToTitle);
        $this->cloneImmutable($multimediaObject, $baseMultimediaObject);

        $multimediaObject->setI18nSubtitle($baseMultimediaObject->getI18nSubtitle());
        $multimediaObject->setI18nDescription($baseMultimediaObject->getI18nDescription());
        $multimediaObject->setI18nLine2($baseMultimediaObject->getI18nLine2());
        $multimediaObject->setI18nKeyword($baseMultimediaObject->getI18nKeyword());
        $multimediaObject->setCopyright($baseMultimediaObject->getCopyright());
        $multimediaObject->setLicense($baseMultimediaObject->getLicense());
        $multimediaObject->setNumview(0);
        $multimediaObject->setComments($baseMultimediaObject->getComments());

        $this->cloneProperties($multimediaObject, $baseMultimediaObject);
        $this->cloneTags($multimediaObject, $baseMultimediaObject->getTags());
        $this->cloneRoles($multimediaObject, $baseMultimediaObject->getRoles());
        $this->cloneGroups($multimediaObject, $baseMultimediaObject->getGroups());

        $this->documentManager->persist($multimediaObject);

        $this->clonePics($multimediaObject, $baseMultimediaObject->getPics());
        $this->cloneTracks($multimediaObject, $baseMultimediaObject->getTracks());
        $this->cloneMaterials($multimediaObject, $baseMultimediaObject->getMaterials());
        $this->cloneLinks($multimediaObject, $baseMultimediaObject->getLinks());
        $this->cloneAnnotations($multimediaObject, $baseMultimediaObject);
        $this->cloneEvents($multimediaObject, $baseMultimediaObject);
        $this->cloneSegments($multimediaObject, $baseMultimediaObject);
        $this->cloneSocial($multimediaObject, $baseMultimediaObject);

        $this->textIndexService->updateMultimediaObjectTextIndex($multimediaObject);

        $clonedEmbeddedBroadcast = $this->embeddedBroadcastService->cloneResource(
            $baseMultimediaObject->getEmbeddedBroadcast()
        );
        $multimediaObject->setEmbeddedBroadcast($clonedEmbeddedBroadcast);

        $multimediaObject->setPublicDate($baseMultimediaObject->getPublicDate());
        $multimediaObject->setRecordDate($baseMultimediaObject->getRecordDate());
        $multimediaObject->setStatus($baseMultimediaObject->getStatus());

        $this->autoNumericValueService->numericalIDForMultimediaObject($multimediaObject);

        $this->documentManager->persist($multimediaObject);
        $this->documentManager->flush();

        $this->multimediaObjectEventDispatcherService->dispatchClone($baseMultimediaObject, $multimediaObject);

        return $multimediaObject;
    }

    public function cloneTitle(
        MultimediaObject $multimediaObject,
        MultimediaObject $baseMultimediaObject,
        string $addToTitle
    ): void {
        $i18nTitles = [];
        foreach ($baseMultimediaObject->getI18nTitle() as $key => $val) {
            $string = $this->translator->trans($addToTitle, [], null, $key);
            if (!empty($addToTitle)) {
                $i18nTitles[$key] = ' ('.$string.') '.$val;
            } else {
                $i18nTitles[$key] = $val;
            }
        }
        $multimediaObject->setI18nTitle($i18nTitles);
    }

    private function cloneImmutable(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        if ($baseMultimediaObject->getImmutable() instanceof Immutable) {
            $multimediaObject->setImmutable($baseMultimediaObject->getImmutable());
        }
    }

    private function cloneProperties(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        $multimediaObject->setProperties($baseMultimediaObject->getProperties());
        $multimediaObject->setProperty('clonedfrom', $baseMultimediaObject->getId());
    }

    private function cloneTags(MultimediaObject $multimediaObject, Collection $tags): void
    {
        foreach ($tags as $tag) {
            $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId(), false);
        }
    }

    private function cloneRoles(MultimediaObject $multimediaObject, Collection $roles): void
    {
        foreach ($roles as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                $multimediaObject->addPersonWithRole($embeddedPerson, $embeddedRole);
            }
        }
    }

    private function cloneGroups(MultimediaObject $multimediaObject, Collection $groups): void
    {
        foreach ($groups as $group) {
            $multimediaObject->addGroup($group);
        }
    }

    private function clonePics(MultimediaObject $multimediaObject, Collection $pics): void
    {
        foreach ($pics as $thumb) {
            $clonedThumb = clone $thumb;
            $this->documentManager->persist($clonedThumb);
            $multimediaObject->addPic($clonedThumb);
        }
    }

    private function cloneTracks(MultimediaObject $multimediaObject, Collection $tracks): void
    {
        foreach ($tracks as $track) {
            $clonedTrack = clone $track;
            $clonedTrack->setNumview(0);
            $this->documentManager->persist($clonedTrack);
            $multimediaObject->addTrack($clonedTrack);
        }
    }

    private function cloneMaterials(MultimediaObject $multimediaObject, Collection $materials): void
    {
        foreach ($materials as $material) {
            $clonedMaterial = clone $material;
            $this->documentManager->persist($clonedMaterial);
            $multimediaObject->addMaterial($clonedMaterial);
        }
    }

    private function cloneLinks(MultimediaObject $multimediaObject, Collection $links): void
    {
        foreach ($links as $link) {
            $clonedLink = clone $link;
            $this->documentManager->persist($clonedLink);
            $multimediaObject->addLink($clonedLink);
        }
    }

    private function cloneAnnotations(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        $annotations = $this->documentManager->getRepository(Annotation::class)->findBy([
            'multimediaObject' => new ObjectId($baseMultimediaObject->getId()),
        ]);

        foreach ($annotations as $annot) {
            $clonedAnnot = clone $annot;
            $clonedAnnot->setMultimediaObject($multimediaObject->getId());
            $this->documentManager->persist($clonedAnnot);
        }
    }

    private function cloneEvents(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        if ($baseMultimediaObject->getEmbeddedEvent()) {
            $multimediaObject->setEmbeddedEvent($baseMultimediaObject->getEmbeddedEvent());
        }
    }

    private function cloneSegments(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        if ($baseMultimediaObject->getEmbeddedSegments()) {
            $multimediaObject->setEmbeddedSegments($baseMultimediaObject->getEmbeddedSegments());
        }
    }

    private function cloneSocial(MultimediaObject $multimediaObject, MultimediaObject $baseMultimediaObject): void
    {
        if ($baseMultimediaObject->getEmbeddedSocial()) {
            $multimediaObject->setEmbeddedSocial($baseMultimediaObject->getEmbeddedSocial());
        }
    }
}
