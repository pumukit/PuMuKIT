<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
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

class JobGeneratorListener
{
    private DocumentManager $documentManager;
    private JobCreator $jobCreator;
    private ProfileService $profileService;
    private LoggerInterface $logger;
    private array $profiles;

    public function __construct(
        DocumentManager $documentManager,
        JobCreator $jobCreator,
        ProfileService $profileService,
        LoggerInterface $logger
    ) {
        $this->documentManager = $documentManager;
        $this->jobCreator = $jobCreator;
        $this->logger = $logger;
        $this->profileService = $profileService;
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

    public function createEncodedTagForMedia(MediaInterface $master, Tag $pubChannel): void
    {
        $tags = Tags::create($master->tags()->toArray());
        $tags->add('ENCODED_'.$pubChannel->getCod());
        $master->updateTags($tags);
        $this->documentManager->flush();
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

        if (!$this->ensureProfileExists($multimediaObject)) {
            return;
        }

        $master = $multimediaObject->getMaster();
        $profile = $this->profileService->getProfile($master->profileName());

        foreach ($tag->getChildren() as $pubChannelTag) {
            if (!$multimediaObject->containsTag($pubChannelTag)) {
                continue;
            }

            if ($this->hasEncodedJobForProfile($master, $pubChannelTag, $profile)) {
                continue;
            }

            $this->createEncodedTagForMedia($master, $pubChannelTag);

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

        $this->hasMediaWithProfileTarget($multimediaObject, $pubChannel);

        $filteredProfiles = $this->profileService->filterProfilesByPubChannel($pubChannel);

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
                $jobOptions = new JobOptions($targetProfile, 2, $master->language(), []);
                $path = Path::create($master->storage()->path()->path());
                $this->jobCreator->fromPath($multimediaObject, $path, $jobOptions);
            }

            if (in_array($pubChannelCod, $targets['force'])) {
                $master = $multimediaObject->getTrackWithTag('master');
                $this->logger->warning(sprintf('JobGeneratorListener creates new job (%s) for multimedia object %s using forced target', $targetProfile, $multimediaObject->getId()));
                $jobOptions = new JobOptions($targetProfile, 2, $master->language(), []);
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
        return $master->tags()->containsTag('ENCODED_'.$pubChannel->getCod()) && str_contains($profile['target'], $pubChannel->getCod());
    }

    private function hasMediaWithProfileTarget(MultimediaObject $multimediaObject, Tag $pubChannel): void
    {
        foreach ($multimediaObject->getTracks() as $track) {
            $profileName = $track->profileName();
            if (!$profileName || !isset($this->profiles[$profileName])) {
                continue;
            }

            $targets = $this->getTargets($this->profiles[$profileName]['target']);
            if (!in_array($pubChannel->getCod(), $targets['standard'])) {
                continue;
            }

            $this->logger->warning(sprintf(
                self::class.
                " can't create new job for object %s because it already contains media with a profile with %s target",
                $multimediaObject->getId(),
                $pubChannel->getCod()
            ));
        }
    }
}
