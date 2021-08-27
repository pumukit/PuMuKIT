<?php

declare(strict_types=1);

namespace Pumukit\WizardBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Symfony\Component\Process\Process;

class WizardService
{
    private $dm;
    private $factoryService;
    private $tagService;
    private $user;
    private $inboxDepth;
    private $locales;
    private $basePath;

    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        TagService $tagService,
        string $basePath,
        array $locales,
        bool $inboxDepth = null
    ) {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->inboxDepth = $inboxDepth;
        $this->tagService = $tagService;
        $this->locales = $locales;
        $this->basePath = $basePath;
    }

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

    public function createSeries(array $seriesData = [])
    {
        if ($seriesData) {
            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) {
                $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);
            }

            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description'];
            $series = $this->factoryService->createSeries($this->user, $seriesData['i18n_title']);

            return $this->setData($series, $seriesData, $keys);
        }

        return null;
    }

    public function getKeyData($key, array $formData, array $default = [])
    {
        return array_key_exists($key, $formData) ? $formData[$key] : $default;
    }

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

    public function createMultimediaObject(array $mmData, Series $series, User $user)
    {
        $multimediaObject = $this->factoryService->createMultimediaObject($series, true, $user);

        if ($mmData) {
            $keys = ['i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2'];
            $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
        }

        return $multimediaObject;
    }

    public function createProcess($aCommandArguments = [])
    {
        $command = [
            'php',
            $this->basePath.'bin/console',
            'pumukit:wizard:import',
        ];

        foreach ($aCommandArguments as $argument) {
            $command[] = $argument;
        }

        $process = new Process($command);

        $command = $process->getCommandLine();

        shell_exec("nohup {$command} 1> /dev/null 2> /dev/null & echo $!");
    }

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

    public function convertPubChannels($pubChannels)
    {
        $keys = [];
        foreach ($pubChannels as $key => $pubChannel) {
            $keys[] = $key;
        }

        return implode(',', $keys);
    }

    public function createCommandArguments($aCommandArguments, $sOption, $sValue)
    {
        array_push($aCommandArguments, $sValue);

        return $aCommandArguments;
    }
}
