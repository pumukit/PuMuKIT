<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Annotation;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\Translation\TranslatorInterface;

class FactoryService
{
    const DEFAULT_SERIES_TITLE = 'New';
    const DEFAULT_MULTIMEDIAOBJECT_TITLE = 'New';

    private $dm;
    private $tagService;
    private $personService;
    private $userService;
    private $embeddedBroadcastService;
    private $seriesService;
    private $mmsDispatcher;
    private $seriesDispatcher;
    private $translator;
    private $locales;
    private $defaultCopyright;
    private $defaultLicense;
    private $addUserAsPerson;

    public function __construct(DocumentManager $documentManager, TagService $tagService, PersonService $personService, UserService $userService, EmbeddedBroadcastService $embeddedBroadcastService, SeriesService $seriesService, MultimediaObjectEventDispatcherService $mmsDispatcher, SeriesEventDispatcherService $seriesDispatcher, TranslatorInterface $translator, $addUserAsPerson = true, array $locales = [], $defaultCopyright = '', $defaultLicense = '')
    {
        $this->dm = $documentManager;
        $this->tagService = $tagService;
        $this->personService = $personService;
        $this->userService = $userService;
        $this->embeddedBroadcastService = $embeddedBroadcastService;
        $this->seriesService = $seriesService;
        $this->mmsDispatcher = $mmsDispatcher;
        $this->seriesDispatcher = $seriesDispatcher;
        $this->translator = $translator;
        $this->locales = $locales;
        $this->defaultCopyright = $defaultCopyright;
        $this->defaultLicense = $defaultLicense;
        $this->addUserAsPerson = $addUserAsPerson;
    }

    /**
     * Get locales.
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Wrapper for createCollection. Creates a TYPE_SERIES collection.
     *
     * @param null|User  $loggedInUser
     * @param null|array $title
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function createSeries(User $loggedInUser = null, array $title = null)
    {
        return $this->createCollection(Series::TYPE_SERIES, $loggedInUser, $title);
    }

    /**
     * Wrapper for createColletion. Creates a TYPE_PLAYLIST collection.
     *
     * @param null|User  $loggedInUser
     * @param null|array $title
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function createPlaylist(User $loggedInUser = null, array $title = null)
    {
        return $this->createCollection(Series::TYPE_PLAYLIST, $loggedInUser, $title);
    }

    /**
     * Internal method to create a new collection (series or playlist) with default values. Not emit events.
     *
     * @param int        $collectionType
     * @param null|User  $loggedInUser
     * @param null|array $title
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function doCreateCollection($collectionType, User $loggedInUser = null, array $title = null)
    {
        $series = new Series();
        $series->setLocale($this->locales[0]);

        $series->setPublicDate(new \DateTime('now'));
        $series->setCopyright($this->defaultCopyright);
        $series->setLicense($this->defaultLicense);
        $series->setType($collectionType);
        if ($title) {
            $series->setI18nTitle($title);
        } else {
            foreach ($this->locales as $locale) {
                $title = $this->translator->trans(self::DEFAULT_SERIES_TITLE, [], null, $locale);
                $series->setTitle($title, $locale);
            }
        }

        $mm = $this->createMultimediaObjectPrototype($series, $loggedInUser);

        $this->dm->persist($mm);
        $this->dm->persist($series);
        $this->dm->flush();

        $this->generateNumericalIDSeries($series);

        return $series;
    }

    /**
     * Create a new collection (series or playlist) with default values.
     *
     * @param int        $collectionType
     * @param null|User  $loggedInUser
     * @param null|array $title
     *
     * @throws \Exception
     *
     * @return Series
     */
    public function createCollection($collectionType, User $loggedInUser = null, array $title = null)
    {
        $series = $this->doCreateCollection($collectionType, $loggedInUser, $title);

        $this->seriesDispatcher->dispatchCreate($series);

        return $series;
    }

    /**
     * Internla method to create a new Multimedia Object from Template. Not emit events.
     *
     * @param Series $series
     * @param bool   $flush
     * @param User   $loggedInUser
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    public function doCreateMultimediaObject(Series $series, $flush = true, User $loggedInUser = null)
    {
        $prototype = $this->getMultimediaObjectPrototype($series);

        if (null !== $prototype) {
            $mm = $this->createMultimediaObjectFromPrototype($prototype);
        } else {
            $mm = new MultimediaObject();
            $mm->setLocale($this->locales[0]);
            foreach ($this->locales as $locale) {
                $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, [], null, $locale);
                $mm->setTitle($title, $locale);
            }
            $mm = $this->embeddedBroadcastService->setByType($mm, EmbeddedBroadcast::TYPE_PUBLIC, false);
        }

        $mm->setSeries($series);

        $mm->setPublicDate(new \DateTime('now'));
        $mm->setRecordDate($mm->getPublicDate());

        $mm->setStatus(MultimediaObject::STATUS_BLOCKED);
        if ($loggedInUser) {
            if ($loggedInUser->hasRole(Permission::INIT_STATUS_PUBLISHED)) {
                $mm->setStatus(MultimediaObject::STATUS_PUBLISHED);
            } elseif ($loggedInUser->hasRole(Permission::INIT_STATUS_HIDDEN)) {
                $mm->setStatus(MultimediaObject::STATUS_HIDDEN);
            }
            foreach ($loggedInUser->getRoles() as $role) {
                if ($pubCh = Permission::getPubChannelForRoleTagDefault($role)) {
                    $this->tagService->addTagByCodToMultimediaObject($mm, $pubCh, false);
                }
            }
        }

        $mm = $this->addLoggedInUserAsPerson($mm, $loggedInUser);
        // Add other owners in case of exists
        foreach ($prototype->getRoles() as $embeddedRole) {
            if ($this->personService->getPersonalScopeRoleCode() === $embeddedRole->getCod()) {
                $role = $this->dm->getRepository(Role::class)->findOneBy(['cod' => $this->personService->getPersonalScopeRoleCode()]);
                foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                    $person = $this->dm->getRepository(Person::class)->findOneBy(['_id' => $embeddedPerson->getId()]);
                    $mm = $this->personService->createRelationPerson($person, $role, $mm);
                }
            }
        }

        $this->dm->persist($mm);
        $this->dm->persist($series);
        if ($flush) {
            $this->dm->flush();
            $this->generateNumericalIDMultimediaObject($mm);
        }

        return $mm;
    }

    /**
     * Create a new Multimedia Object from Template.
     *
     * @param Series $series
     * @param bool   $flush
     * @param User   $loggedInUser
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    public function createMultimediaObject(Series $series, $flush = true, User $loggedInUser = null)
    {
        $mm = $this->doCreateMultimediaObject($series, $flush, $loggedInUser);

        //$this->seriesDispatcher->dispatchUpdate($series);
        $this->mmsDispatcher->dispatchCreate($mm);

        return $mm;
    }

    /**
     * Get series by id.
     *
     * @param string $id
     * @param string $sessionId
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     *
     * @return null|object Series
     */
    public function findSeriesById($id, $sessionId = null)
    {
        $repo = $this->dm->getRepository(Series::class);

        if (null !== $id) {
            $series = $repo->find($id);
        } elseif (null !== $sessionId) {
            $series = $repo->find($sessionId);
        } else {
            return null;
        }

        return $series;
    }

    /**
     * Get multimediaObject by id.
     *
     * @param string $id
     *
     * @throws \Doctrine\ODM\MongoDB\LockException
     * @throws \Doctrine\ODM\MongoDB\Mapping\MappingException
     *
     * @return object
     */
    public function findMultimediaObjectById($id)
    {
        $repo = $this->dm->getRepository(MultimediaObject::class);

        return $repo->find($id);
    }

    /**
     * Get parent tags.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return object
     */
    public function getParentTags()
    {
        $repo = $this->dm->getRepository(Tag::class);

        return $repo->findOneByCod('ROOT')->getChildren();
    }

    /**
     * Get multimedia object template.
     *
     * @param Series $series
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return object MultimediaObject
     */
    public function getMultimediaObjectPrototype(Series $series = null)
    {
        return $this->dm
            ->getRepository(MultimediaObject::class)
            ->findPrototype($series)
        ;
    }

    /**
     * Get tags by cod.
     *
     * @param string $cod
     * @param bool   $getChildren
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return mixed $tags
     */
    public function getTagsByCod($cod, $getChildren)
    {
        $repository = $this->dm->getRepository(Tag::class);

        $tags = $repository->findOneByCod($cod);

        if ($tags && $getChildren) {
            return $tags->getChildren();
        }

        return $tags;
    }

    /**
     * Delete Series.
     *
     * @param Series $series
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function deleteSeries(Series $series)
    {
        $repoMmobjs = $this->dm->getRepository(MultimediaObject::class);

        $multimediaObjects = $repoMmobjs->findBySeries($series);
        foreach ($multimediaObjects as $mm) {
            $this->dm->remove($mm);
            $this->mmsDispatcher->dispatchDelete($mm);
        }

        $this->dm->remove($series);
        $this->dm->flush();

        $this->seriesDispatcher->dispatchDelete($series);
    }

    /**
     * Delete MultimediaObject.
     *
     * @param MultimediaObject $multimediaObject
     */
    public function deleteMultimediaObject(MultimediaObject $multimediaObject)
    {
        if (null !== $series = $multimediaObject->getSeries()) {
            $this->seriesDispatcher->dispatchUpdate($series);
        }
        $annotRepo = $this->dm->getRepository(Annotation::class);
        $annotations = $annotRepo->findBy(['multimediaObject' => new \MongoId($multimediaObject->getId())]);
        foreach ($annotations as $annot) {
            $this->dm->remove($annot);
        }
        $this->dm->remove($multimediaObject);
        $this->dm->flush();

        $this->mmsDispatcher->dispatchDelete($multimediaObject);
    }

    /**
     * Delete resource.
     *
     * @param object $resource
     */
    public function deleteResource($resource)
    {
        if ($resource instanceof User) {
            $this->userService->delete($resource);
        } else {
            $this->dm->remove($resource);
            $this->dm->flush();
        }
    }

    /**
     * @param Series $series
     *
     * @throws \Exception
     */
    public function cloneSeries(Series $series)
    {
        $newSeries = new Series();
        $i18nTitles = [];
        foreach ($series->getI18nTitle() as $key => $val) {
            $string = $this->translator->trans('cloned', [], null, $key);
            $i18nTitles[$key] = $val.' ('.$string.')';
        }

        $newSeries->setI18nTitle($i18nTitles);

        $this->dm->persist($newSeries);
        $this->dm->flush();

        $multimediaObjectPrototype = $this->dm->getRepository(MultimediaObject::class)->findOneBy(['status' => MultimediaObject::STATUS_PROTOTYPE, 'series' => $series->getId()]);
        $this->cloneMultimediaObject($multimediaObjectPrototype, $newSeries);

        $multimediaObjects = $this->dm->getRepository(MultimediaObject::class)->findBy(['series' => $series->getId()]);
        foreach ($multimediaObjects as $multimediaObject) {
            if (!$multimediaObject->isLive()) {
                $this->cloneMultimediaObject($multimediaObject, $newSeries);
            }
        }

        $newSeries->setAnnounce($series->getAnnounce());
        $newSeries->setProperties($series->getProperties());
        $newSeries->setI18nDescription($series->getI18nDescription());
        $newSeries->setI18nFooter($series->getI18nFooter());
        $newSeries->setI18nHeader($series->getI18nHeader());
        $newSeries->setI18nLine2($series->getI18nLine2());
        $newSeries->setI18nSubtitle($series->getI18nSubtitle());
        $newSeries->setI18nKeywords($series->getI18nKeywords());
        $newSeries->setHide($series->getHide());
        $newSeries->setCopyright($series->getCopyright());
        $newSeries->setLicense($series->getLicense());
        $newSeries->setPlaylist($series->getPlaylist());
        if ($series->getSeriesType() instanceof SeriesType) {
            $newSeries->setSeriesType($series->getSeriesType());
        }
        $newSeries->setSeriesStyle($series->getSeriesStyle());
        $newSeries->setPublicDate($series->getPublicDate());

        foreach ($series->getPics() as $thumb) {
            $clonedThumb = clone $thumb;
            $this->dm->persist($clonedThumb);
            $newSeries->addPic($clonedThumb);
        }

        $this->dm->flush();

        $this->generateNumericalIDSeries($newSeries);

        $this->seriesDispatcher->dispatchCreate($series);
    }

    /**
     * Clone a multimedia object.
     *
     * @param MultimediaObject $src
     * @param null|Series      $series
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    public function cloneMultimediaObject(MultimediaObject $src, Series $series = null)
    {
        $new = new MultimediaObject();
        $new->setLocale($this->locales[0]);
        if ($series) {
            $new->setSeries($series);
        } else {
            $new->setSeries($src->getSeries());
        }
        $new->setType($src->getType());

        $i18nTitles = [];
        foreach ($src->getI18nTitle() as $key => $val) {
            $string = $this->translator->trans('cloned', [], null, $key);
            $i18nTitles[$key] = $val.' ('.$string.')';
        }
        $new->setI18nTitle($i18nTitles);
        $new->setI18nSubtitle($src->getI18nSubtitle());
        $new->setI18nDescription($src->getI18nDescription());
        $new->setI18nLine2($src->getI18nLine2());
        $new->setI18nKeyword($src->getI18nKeyword());
        $new->setCopyright($src->getCopyright());
        $new->setLicense($src->getLicense());
        $new->setNumview(0);
        $new->setTextIndex($src->getTextIndex());
        $new->setSecondaryTextIndex($src->getSecondaryTextIndex());
        // NOTE: #7408 Specify which properties are clonable
        $new->setProperty('subseries', $src->getProperty('subseries'));
        $new->setProperty('subseriestitle', $src->getProperty('subseriestitle'));
        $new->setProperty('owners', $src->getProperty('owners'));

        $new->setProperty('clonedfrom', $src->getId());

        foreach ($src->getTags() as $tag) {
            $this->tagService->addTagToMultimediaObject($new, $tag->getId(), false);
        }

        foreach ($src->getRoles() as $embeddedRole) {
            foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                $new->addPersonWithRole($embeddedPerson, $embeddedRole);
            }
        }

        foreach ($src->getGroups() as $group) {
            $new->addGroup($group);
        }

        $this->dm->persist($new);
        foreach ($src->getPics() as $thumb) {
            $clonedThumb = clone $thumb;
            $this->dm->persist($clonedThumb);
            $new->addPic($clonedThumb);
        }
        foreach ($src->getTracks() as $track) {
            $clonedTrack = clone $track;
            $clonedTrack->setNumview(0);
            $this->dm->persist($clonedTrack);
            $new->addTrack($clonedTrack);
        }
        foreach ($src->getMaterials() as $material) {
            $clonedMaterial = clone $material;
            $this->dm->persist($clonedMaterial);
            $new->addMaterial($clonedMaterial);
        }
        foreach ($src->getLinks() as $link) {
            $clonedLink = clone $link;
            $this->dm->persist($clonedLink);
            $new->addLink($clonedLink);
        }
        $annotRepo = $this->dm->getRepository(Annotation::class);
        $annotations = $annotRepo->findBy(['multimediaObject' => new \MongoId($src->getId())]);
        foreach ($annotations as $annot) {
            $clonedAnnot = clone $annot;
            $clonedAnnot->setMultimediaObject($new->getId());
            $this->dm->persist($clonedAnnot);
        }

        $this->dm->flush();

        if ($embeddedBroadcast = $src->getEmbeddedBroadcast()) {
            $clonedEmbeddedBroadcast = $this->embeddedBroadcastService->cloneResource($embeddedBroadcast);
            $new->setEmbeddedBroadcast($clonedEmbeddedBroadcast);
        } else {
            $new = $this->embeddedBroadcastService->setByType($new, EmbeddedBroadcast::TYPE_PUBLIC, false);
        }

        $new->setPublicDate($src->getPublicDate());
        $new->setRecordDate($src->getRecordDate());
        if ($series && MultimediaObject::STATUS_PROTOTYPE == $src->getStatus()) {
            $new->setStatus($src->getStatus());
        } else {
            $new->setStatus(MultimediaObject::STATUS_BLOCKED);
        }

        $this->dm->persist($new);
        $this->dm->flush();
        $this->generateNumericalIDMultimediaObject($new);

        $this->mmsDispatcher->dispatchClone($src, $new);

        return $new;
    }

    /**
     * Get default Multimedia Object i18n title.
     *
     * @return array
     */
    public function getDefaultMultimediaObjectI18nTitle()
    {
        $i18nTitle = [];
        foreach ($this->locales as $locale) {
            $i18nTitle[$locale] = self::DEFAULT_MULTIMEDIAOBJECT_TITLE;
        }

        return $i18nTitle;
    }

    /**
     * Get default Series i18n title.
     *
     * @return array
     */
    public function getDefaultSeriesI18nTitle()
    {
        $i18nTitle = [];
        foreach ($this->locales as $locale) {
            $i18nTitle[$locale] = self::DEFAULT_SERIES_TITLE;
        }

        return $i18nTitle;
    }

    /**
     * Create a new Multimedia Object Template.
     *
     * @param Series $series
     * @param User   $loggedInUser
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    private function createMultimediaObjectPrototype(Series $series, User $loggedInUser = null)
    {
        $mm = new MultimediaObject();
        $mm->setLocale($this->locales[0]);
        $mm->setSeries($series);

        $mm->setStatus(MultimediaObject::STATUS_PROTOTYPE);
        $embeddedBroadcast = $this->embeddedBroadcastService->createPublicEmbeddedBroadcast();
        $mm->setEmbeddedBroadcast($embeddedBroadcast);
        $mm->setPublicDate(new \DateTime('now'));
        $mm->setRecordDate($mm->getPublicDate());
        $mm->setCopyright($this->defaultCopyright);
        $mm->setLicense($this->defaultLicense);
        foreach ($this->locales as $locale) {
            $title = $this->translator->trans(self::DEFAULT_MULTIMEDIAOBJECT_TITLE, [], null, $locale);
            $mm->setTitle($title, $locale);
        }

        $this->generateNumericalIDMultimediaObject($mm);

        return $this->addLoggedInUserAsPerson($mm, $loggedInUser);
    }

    /**
     * Create multimedia object from prototype.
     *
     * @param MultimediaObject $prototype
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    private function createMultimediaObjectFromPrototype(MultimediaObject $prototype)
    {
        $new = new MultimediaObject();
        $new->setLocale($this->locales[0]);

        $new->setI18nTitle($prototype->getI18nTitle());
        $new->setI18nSubtitle($prototype->getI18nSubtitle());
        $new->setI18nDescription($prototype->getI18nDescription());
        $new->setI18nLine2($prototype->getI18nLine2());
        $new->setI18nKeyword($prototype->getI18nKeyword());
        $new->setCopyright($prototype->getCopyright());
        $new->setLicense($prototype->getLicense());

        if ($embeddedBroadcast = $prototype->getEmbeddedBroadcast()) {
            $clonedEmbeddedBroadcast = $this->embeddedBroadcastService->cloneResource($embeddedBroadcast);
            $new->setEmbeddedBroadcast($clonedEmbeddedBroadcast);
        } else {
            $new = $this->embeddedBroadcastService->setByType($new, EmbeddedBroadcast::TYPE_PUBLIC, false);
        }

        foreach ($prototype->getTags() as $tag) {
            $this->tagService->addTagToMultimediaObject($new, $tag->getId(), false);
        }

        // Create roles except Owners because $this->personService->getPersonalScopeRoleCode() !== $embeddedRole->getCod()
        if ($prototype) {
            foreach ($prototype->getRoles() as $embeddedRole) {
                if ($this->personService->getPersonalScopeRoleCode() !== $embeddedRole->getCod()) {
                    foreach ($embeddedRole->getPeople() as $embeddedPerson) {
                        $new->addPersonWithRole($embeddedPerson, $embeddedRole);
                    }
                }
            }
        }

        foreach ($prototype->getGroups() as $group) {
            $new->addGroup($group);
        }

        return $new;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param null|User        $loggedInUser
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    private function addLoggedInUserAsPerson(MultimediaObject $multimediaObject, User $loggedInUser = null)
    {
        if ($this->addUserAsPerson && (null !== $person = $this->personService->getPersonFromLoggedInUser($loggedInUser))) {
            if (null !== $role = $this->personService->getPersonalScopeRole()) {
                $multimediaObject = $this->personService->createRelationPerson($person, $role, $multimediaObject);
            }
        }

        return $multimediaObject;
    }

    private function generateNumericalIDMultimediaObject($mm)
    {
        $SEMKey = 55555;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);

        $enableFilters = array_keys($this->dm->getFilterCollection()->getEnabledFilters());
        foreach ($enableFilters as $enableFilter) {
            $this->dm->getFilterCollection()->disable($enableFilter);
        }

        $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->createQueryBuilder()
            ->field('numerical_id')->exists(true)
            ->sort(['numerical_id' => -1])
            ->getQuery()
            ->getSingleResult()
        ;

        $lastNumericalID = 0;
        if ($multimediaObject) {
            $lastNumericalID = $multimediaObject->getNumericalID();
        }

        $newNumericalID = $lastNumericalID + 1;

        $mm->setNumericalID($newNumericalID);
        $this->dm->flush();

        foreach ($enableFilters as $enableFilter) {
            $this->dm->getFilterCollection()->enable($enableFilter);
        }

        sem_release($seg);
    }

    private function generateNumericalIDSeries($oneSeries)
    {
        $SEMKey = 66666;
        $seg = sem_get($SEMKey, 1, 0666, -1);
        sem_acquire($seg);

        $enableFilters = array_keys($this->dm->getFilterCollection()->getEnabledFilters());
        foreach ($enableFilters as $enableFilter) {
            $this->dm->getFilterCollection()->disable($enableFilter);
        }

        $series = $this->dm->getRepository(Series::class)->createQueryBuilder()
            ->field('numerical_id')->exists(true)
            ->sort(['numerical_id' => -1])
            ->getQuery()
            ->getSingleResult()
        ;

        $lastNumericalID = 0;
        if ($series) {
            $lastNumericalID = $series->getNumericalID();
        }

        $newNumericalID = $lastNumericalID + 1;

        $oneSeries->setNumericalID($newNumericalID);
        $this->dm->flush();

        foreach ($enableFilters as $enableFilter) {
            $this->dm->getFilterCollection()->enable($enableFilter);
        }

        sem_release($seg);
    }
}
