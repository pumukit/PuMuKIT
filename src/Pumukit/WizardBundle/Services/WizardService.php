<?php

namespace Pumukit\WizardBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use http\Exception\UnexpectedValueException;
use Pumukit\EncoderBundle\Services\JobService;
use Pumukit\InspectionBundle\Services\InspectionServiceInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
     * @var AuthorizationChecker
     */
    private $authorizationChecker;

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
     * @param DocumentManager            $documentManager
     * @param FactoryService             $factoryService
     * @param InspectionServiceInterface $inspectionService
     * @param FormEventDispatcherService $formEventDispatcher
     * @param JobService                 $jobService
     * @param AuthorizationChecker       $authorizationChecker
     * @param                            $basePath
     * @param                            $locales
     * @param null                       $inboxDepth
     */
    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        InspectionServiceInterface $inspectionService,
        FormEventDispatcherService $formEventDispatcher,
        JobService $jobService,
        AuthorizationCheckerInterface $authorizationChecker,
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
        $this->locales = $locales;
        $this->basePath = $basePath;
    }

    /**
     * @param User  $user
     * @param       $files
     * @param       $seriesData
     * @param       $formData
     * @param array $options
     *
     * @return mixed|null|object|Series
     *
     * @throws \Exception
     */
    public function uploadMultipleFiles($user, $files, $seriesData, $formData, $options = [])
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
     * @return mixed|null|object|Series
     *
     * @throws \Exception
     */
    public function getSeries(array $seriesData = [])
    {
        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)) {
            $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findOneBy(
                [
                    '_id' => $seriesId,
                ]
            );
        } else {
            $series = $this->createSeries($seriesData);
        }

        return $series;
    }

    /**
     * @param array $seriesData
     *
     * @return mixed|null|Series
     *
     * @throws \Exception
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
            $series = $this->setData($series, $seriesData, $keys);

            return $series;
        }

        return null;
    }

    /**
     * @param       $key
     * @param array $formData
     * @param array $default
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
     * @param $resource
     * @param $resourceData
     * @param $keys
     *
     * @return mixed
     */
    public function setData($resource, $resourceData, $keys)
    {
        foreach ($keys as $key) {
            $value = $this->getKeyData($key, $resourceData);
            if ($value) {
                $upperField = $this->getUpperFieldName($key);
                $setField = 'set'.$upperField;
                $resource->$setField($value);
            }
        }

        $this->dm->persist($resource);
        $this->dm->flush();

        return $resource;
    }

    /**
     * @param MultimediaObject $multimediaObject
     * @param Series           $series
     */
    public function removeInvalidMultimediaObject(MultimediaObject $multimediaObject, Series $series)
    {
        $series->removeMultimediaObject($multimediaObject);
        $this->dm->remove($multimediaObject);
        $this->dm->flush();
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
     * @param                  $tagCode
     *
     * @return array
     */
    public function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode)
    {
        $addedTags = [];

        if ($this->authorizationChecker->isGranted(Permission::getRoleTagDisableForPubChannel($tagCode))) {
            return $addedTags;
        }

        $tagService = $this->get('pumukitschema.tag');
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $tagRepo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $tag = $tagRepo->findOneByCod($tagCode);
        if ($tag) {
            $addedTags = $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());
        }

        return $addedTags;
    }

    /**
     * @param $mmData
     * @param $series
     * @param $user
     *
     * @return mixed|null|MultimediaObject
     *
     * @throws \Exception
     */
    public function createMultimediaObject($mmData, $series, $user)
    {
        if ($series) {
            $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);

            if ($mmData) {
                $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
                $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
            }

            return $multimediaObject;
        }

        return null;
    }

    /**
     * @param array $aCommandArguments
     *
     * @return mixed
     */
    public function createProcess($aCommandArguments = [])
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix('php app/console pumukit:wizard:import');
        $builder->setArguments($aCommandArguments);

        $builder->setTimeout(3600);
        $builder->setWorkingDirectory($this->basePath);
        $process = $builder->getProcess();

        try {
            $process->mustRun();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            $aResult = json_decode($process->getOutput(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new UnexpectedValueException(json_last_error_msg());
            }

            return $aResult;
        } catch (ProcessFailedException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param User $user
     * @param      $selectedPath
     * @param      $inboxDepth
     * @param      $series
     * @param      $status
     * @param      $pubChannel
     * @param      $profile
     * @param      $priority
     * @param      $language
     * @param      $description
     *
     * @return mixed
     */
    public function uploadFiles($user, $selectedPath, $inboxDepth, $series, $status, $pubChannel, $profile, $priority, $language, $description)
    {
        $aCommandArguments = [];
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--user', $user);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--path', $selectedPath);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--inbox-depth', $inboxDepth);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--series', $series);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--status', $status);

        $pubChannels = $this->convertPubChannels($pubChannel);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--channels', $pubChannels);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--profile', $profile);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--priority', $priority);
        $aCommandArguments = $this->createCommandArguments($aCommandArguments, '--language', $language);

        //$aCommandArguments = $this->createCommandArguments($aCommandArguments, '--description', $description);

        return $this->createProcess($aCommandArguments);
    }

    /**
     * @param $pubChannels
     *
     * @return string
     */
    public function convertPubChannels($pubChannels)
    {
        $keys = [];
        foreach ($pubChannels as $pubChannel) {
            $keys[] = $pubChannel->getCod();
        }

        return implode(',', $keys);
    }

    /**
     * @param $aCommandArguments
     * @param $sOption
     * @param $sValue
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
