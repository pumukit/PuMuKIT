<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MediaType\MediaInterface;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Document\ValueObject\Tags;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;
use Pumukit\SchemaBundle\Services\MediaUpdater;

class JobGeneratorListener
{
    private DocumentManager $documentManager;
    private JobCreator $jobCreator;
    private ProfileService $profileService;
    private MediaUpdater $mediaUpdater;
    private LoggerInterface $logger;
    private array $profiles;

    public function __construct(
        DocumentManager $documentManager,
        JobCreator $jobCreator,
        ProfileService $profileService,
        MediaUpdater $mediaUpdater,
        LoggerInterface $logger
    ) {
        $this->documentManager = $documentManager;
        $this->jobCreator = $jobCreator;
        $this->profileService = $profileService;
        $this->mediaUpdater = $mediaUpdater;
        $this->logger = $logger;
        $this->profiles = $profileService->getProfiles();
    }

    public function onJobSuccess(JobEvent $event): void
    {
        $this->checkMultimediaObject($event->getMultimediaObject());
    }

    public function onMultimediaObjectUpdate(MultimediaObjectEvent $event): void
    {
        $this->checkMultimediaObject($event->getMultimediaObject());
    }

    private function createEncodedTagForMedia(MultimediaObject $multimediaObject, MediaInterface $master, Tag $pubChannel): void
    {
        $tags = Tags::create($master->tags()->toArray());
        $tags->add('ENCODED_'.$pubChannel->getCod());
        $this->mediaUpdater->updateTags($multimediaObject, $master, $tags);
    }

    private function checkMultimediaObject(MultimediaObject $multimediaObject): void
    {
        if (!$this->shouldGenerateJobs($multimediaObject)) {
            return;
        }

        $tag = $this->ensurePubChannelsTagExists();
        if (!$tag instanceof Tag) {
            return;
        }

        if (!$profile = $this->ensureProfileExists($multimediaObject)) {
            return;
        }

        foreach ($tag->getChildren() as $pubChannelTag) {
            if (!$multimediaObject->containsTag($pubChannelTag)) {
                continue;
            }

            if ($this->hasEncodedJobForProfile($multimediaObject->getMaster(), $pubChannelTag, $profile)) {
                continue;
            }

            $this->createEncodedTagForMedia($multimediaObject, $multimediaObject->getMaster(), $pubChannelTag);

            $this->generateJobs($multimediaObject, $pubChannelTag);
        }
    }

    private function generateJobs(MultimediaObject $multimediaObject, Tag $pubChannel): void
    {
        $default_profiles = $this->profileService->defaultProfilesByMultimediaObjectAndPubChannel(
            $multimediaObject,
            $pubChannel
        );

        $pubChannelCod = $pubChannel->getCod();

        $hasMedia = $this->hasMediaWithProfileTarget($multimediaObject, $pubChannel);
        if ($hasMedia) {
            return;
        }

        //        $filteredProfiles = $this->profileService->filterProfilesByPubChannel($pubChannel);
        $filteredProfiles = $this->profileService->filterProfilesByPubChannelAndType($pubChannel, $multimediaObject);

        $validateDefaultProfiles = explode(' ', $default_profiles);
        $validateDefaultProfiles = array_map(
            static function ($element) { return trim($element); },
            $validateDefaultProfiles
        );

        foreach ($filteredProfiles as $targetProfile => $profile) {
            if (!in_array($targetProfile, $validateDefaultProfiles)) {
                continue;
            }

            $targets = $this->getTargets($profile['target']);

            $track = $multimediaObject->getTrackWithTag('profile:'.$targetProfile);
            if ($track) {
                continue;
            }

            if ($multimediaObject->isVideoAudioType() && 0 !== (is_countable($default_profiles) ? count($default_profiles) : 0)) {
                if (!isset($default_profiles[$pubChannelCod])) {
                    continue;
                }
                if (!$multimediaObject->isOnlyAudio() && !str_contains($default_profiles[$pubChannelCod]['video'], (string) $targetProfile)) {
                    continue;
                }
                if ($multimediaObject->isOnlyAudio() && !str_contains($default_profiles[$pubChannelCod]['audio'], (string) $targetProfile)) {
                    continue;
                }
            }

            if (in_array($pubChannelCod, $targets['standard'])) {
                $master = $multimediaObject->getTrackWithTag('master');
                $this->logger->warning(sprintf('JobGeneratorListener creates new job (%s) for multimedia object %s using standard target', $targetProfile, $multimediaObject->getId()));
                $jobOptions = new JobOptions($targetProfile, 2, $master->language(), [], [], 0, 0, true);
                $path = Path::create($master->storage()->path()->path());
                $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
            }

            if (in_array($pubChannelCod, $targets['force'])) {
                $master = $multimediaObject->getTrackWithTag('master');
                $this->logger->warning(sprintf('JobGeneratorListener creates new job (%s) for multimedia object %s using forced target', $targetProfile, $multimediaObject->getId()));
                $jobOptions = new JobOptions($targetProfile, 2, $master->language(), [], [], 0, 0, true);
                $path = Path::create($master->storage()->path()->path());
                $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
            }
        }
    }

    private function getTargets(string $targets): array
    {
        $return = ['standard' => [], 'force' => []];

        foreach (array_filter(preg_split('/[,\s]+/', $targets)) as $target) {
            if (str_ends_with($target, '*')) {
                $return['force'][] = substr($target, 0, -1);
            } else {
                $return['standard'][] = $target;
            }
        }

        return $return;
    }

    private function shouldGenerateJobs(MultimediaObject $multimediaObject): bool
    {
        if ($multimediaObject->isExternalType()) {
            return false;
        }

        if ($multimediaObject->getTracksWithAnyTag(['display'])) {
            return false;
        }

        return $multimediaObject->getMaster() && !$multimediaObject->isMultistream();
    }

    private function ensurePubChannelsTagExists(): ?Tag
    {
        return $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
    }

    private function ensureProfileExists(MultimediaObject $multimediaObject): ?array
    {
        $profileName = $multimediaObject->getMaster()->profileName();

        return $this->profileService->getProfile($profileName);
    }

    private function hasEncodedJobForProfile(MediaInterface $master, Tag $pubChannel, array $profile): bool
    {
        return $master->tags()->containsTag('ENCODED_'.$pubChannel->getCod()) && isset($profile['target'])
            && is_string($profile['target']) && str_contains($profile['target'], $pubChannel->getCod());
    }

    private function hasMediaWithProfileTarget(MultimediaObject $multimediaObject, Tag $pubChannel): bool
    {
        $hasMediaOrJob = false;

        foreach ($multimediaObject->getTracks() as $track) {
            $profileName = $track->profileName();
            if (!$profileName || !isset($this->profiles[$profileName])) {
                continue;
            }

            if (!isset($this->profiles[$profileName]['target']) || !is_string($this->profiles[$profileName]['target'])) {
                continue;
            }

            $targets = $this->getTargets($this->profiles[$profileName]['target']);
            if (!in_array($pubChannel->getCod(), $targets['standard'])) {
                continue;
            }

            $hasMediaOrJob = true;
            $this->logger->warning(sprintf(
                self::class.
                " can't create new job for object %s because it already contains media with a profile with %s target",
                $multimediaObject->getId(),
                $pubChannel->getCod()
            ));

            break;
        }

        if (!$hasMediaOrJob) {
            $pending_jobs = $this->documentManager->getRepository(Job::class)->findNotFinishedByMultimediaObjectId(
                $multimediaObject->getId()
            );

            foreach ($pending_jobs as $job) {
                $targetJob = $this->profiles[$job->getProfile()]['target'];
                $tagsJob = $this->profiles[$job->getProfile()]['tags'];
                if (str_contains($targetJob, $pubChannel->getCod())) {
                    if (!str_contains($tagsJob, 'dynamic')) {
                        $hasMediaOrJob = true;

                        break;
                    }
                }
            }
        }

        return $hasMediaOrJob;
    }
}
