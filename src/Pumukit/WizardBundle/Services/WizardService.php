<?php

namespace Pumukit\WizardBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class WizardService.
 */
class WizardService
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var FactoryService
     */
    private $factoryService;

    /**
     * @var InspectionServiceInterface
     */
    private $inspectionService;

    /**
     * @var FormEventDispatcherService
     */
    private $formEventDispatcher;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TagService
     */
    private $tagService;

    /**
     * @var User
     */
    private $user;

    private $inboxDepth;
    private $locales;
    private $basePath;

    /**
     * WizardService constructor.
     *
     * @param DocumentManager               $documentManager
     * @param FactoryService                $factoryService
     * @param InspectionServiceInterface    $inspectionService
     * @param FormEventDispatcherService    $formEventDispatcher
     * @param JobService                    $jobService
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TagService                    $tagService
     * @param string                        $basePath
     * @param array                         $locales
     * @param bool|null                     $inboxDepth
     */
    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        InspectionServiceInterface $inspectionService,
        FormEventDispatcherService $formEventDispatcher,
        JobService $jobService,
        AuthorizationCheckerInterface $authorizationChecker,
        TagService $tagService,
        $basePath,
        $locales,
        $inboxDepth = null
    ) {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->inspectionService = $inspectionService;
        $this->formEventDispatcher = $formEventDispatcher;
        $this->inboxDepth = $inboxDepth;
        $this->jobService = $jobService;
        $this->authorizationChecker = $authorizationChecker;
        $this->tagService = $tagService;
        $this->locales = $locales;
        $this->basePath = $basePath;
    }

    /**
     * @param User   $user
     * @param string $files
     * @param array  $seriesData
     * @param array  $options
     *
     * @throws \Exception
     *
     * @return mixed|object|Series|null
     */
    public function uploadMultipleFiles($user, $files, $seriesData, $options = [])
    {
        $series = $this->getSeries($seriesData);

        $selectedPath = $files;

        try {
            $this->uploadFiles(
                $user,
                $selectedPath,
                $this->inboxDepth ? 1 : 0,
                $series->getId(),
                $options['status'],
                $options['pubChannel'],
                $options['profile'],
                $options['priority'],
                $options['language'],
                $options['description']
            );
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        return $series;
    }

    /**
     * @param array $seriesData
     *
     * @throws \Exception
     *
     * @return mixed|object|Series|null
     */
    public function getSeries(array $seriesData = [])
    {
        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)) {
            $series = $this->dm->getRepository(Series::class)->findOneBy([
                '_id' => $seriesId,
            ]);
        } else {
            $series = $this->createSeries($seriesData);
        }

        return $series;
    }

    /**
     * @param array $seriesData
     *
     * @throws \Exception
     *
     * @return mixed|Series|null
     */
    public function createSeries(array $seriesData = [])
    {
        if ($seriesData) {
            $series = $this->factoryService->createSeries($this->user);

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);
            }

            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description'];

            return $this->setData($series, $seriesData, $keys);
        }

        return null;
    }

    /**
     * @param string $key
     * @param array  $formData
     * @param array  $default
     *
     * @return mixed
     */
    public function getKeyData($key, array $formData, array $default = [])
    {
        return array_key_exists($key, $formData) ? $formData[$key] : $default;
    }

    /**
     * @param array  $resourceData
     * @param string $fieldName
     * @param string $defaultValue
     * @param bool   $isI18nField
     *
     * @return array
     */
    public function getDefaultFieldValuesInData($resourceData = [], $fieldName = '', $defaultValue = '', $isI18nField = false)
    {
        if ($fieldName && $defaultValue) {
            if ($isI18nField) {
                $resourceData[$fieldName] = [];
                foreach ($this->locales as $locale) {
                    $resourceData[$fieldName][$locale] = $defaultValue;
                }
            } else {
                $resourceData[$fieldName] = $defaultValue;
            }
        }

        return $resourceData;
    }

    /**
     * @param object $resource
     * @param array  $resourceData
     * @param array  $keys
     *
     * @return mixed
     */
    public function setData($resource, $resourceData, $keys)
    {
        foreach ($keys as $key) {
            $value = $this->getKeyData($key, $resourceData);
            $filterValue = array_filter($value);
            if (0 !== count($filterValue)) {
                $upperField = $this->getUpperFieldName($key);
                $setField = 'set'.$upperField;
                $resource->{$setField}($value);
            }
        }

        $this->dm->persist($resource);
        $this->dm->flush();

        return $resource;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getUpperFieldName($key = '')
    {
        $pattern = '/_[a-z]?/';
        $aux = preg_replace_callback(
            $pattern,
            function ($matches) {
                return strtoupper(ltrim($matches[0], '_'));
            },
            $key
        );

        return ucfirst($aux);
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param string           $tagCode
     * @param User             $user
     *
     * @throws \Exception
     *
     * @return array
     */
    public function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode, User $user)
    {
        $addedTags = [];

        if ($user->hasRole(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tag = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => $tagCode]);
        if ($tag) {
            $addedTags = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    /**
     * @param array  $mmData
     * @param Series $series
     * @param User   $user
     *
     * @throws \Exception
     *
     * @return MultimediaObject
     */
    public function createMultimediaObject(array $mmData, Series $series, User $user)
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);

        if ($mmData) {
            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
            $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
        }

        return $multimediaObject;
    }

    /**
     * @param array $aCommandArguments
     *
     * @return mixed
     */
    public function createProcess($aCommandArguments = [])
    {
        $builder = new ProcessBuilder();
        $console = $this->basePath.'app/console';

        $builder->add('php')->add($console);

        $builder->add('pumukit:wizard:import');
        foreach ($aCommandArguments as $argument) {
            $builder->add($argument);
        }

        $process = $builder->getProcess();

        $command = $process->getCommandLine();

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }

    /**
     * @param User   $user
     * @param string $selectedPath
     * @param int    $inboxDepth
     * @param string $series
     * @param string $status
     * @param array  $pubChannel
     * @param string $profile
     * @param string $priority
     * @param string $language
     * @param string $description
     *
     * @return mixed
     */
    public function uploadFiles($user, $selectedPath, int $inboxDepth, $series, $status, array $pubChannel, $profile, $priority, $language, $description)
    {
        $aCommandArguments = [];
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--user', $user);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--path', $selectedPath);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--inbox-depth', (string) $inboxDepth);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--series', $series);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--status', $status);

        $tags = $this->convertPubChannels($pubChannel);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--channels', $tags);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--profile', $profile);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--priority', $priority);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--language', $language);

        return $this->createProcess($aCommandArguments);
    }

    /**
     * @param array $pubChannels
     *
     * @return string
     */
    public function convertPubChannels($pubChannels)
    {
        $keys = [];
        foreach ($pubChannels as $key => $pubChannel) {
            $keys[] = $key;
        }

        return implode(',', $keys);
    }

    /**
     * @param array  $aCommandArguments
     * @param string $sOption
     * @param string $sValue
     *
     * @return mixed
     */
    public function createCommandArguments($aCommandArguments, $sOption, $sValue)
    {
        //array_push($aCommandArguments, $sOption);
        array_push($aCommandArguments, $sValue);

        return $aCommandArguments;
    }
}
