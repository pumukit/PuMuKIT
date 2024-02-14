<?php

declare(strict_types=1);

namespace Pumukit\WorkflowBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Event\JobEvent;
use Pumukit\EncoderBundle\Services\DTO\JobOptions;
use Pumukit\EncoderBundle\Services\JobCreator;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Document\ValueObject\Path;
use Pumukit\SchemaBundle\Event\MultimediaObjectEvent;

class JobGeneratorListener
{
    private DocumentManager $dm;
    private JobCreator $jobCreator;
    private ProfileService $profileService;
    private LoggerInterface $logger;
    private array $profiles;

    public function __construct(
        DocumentManager $documentManager,
        JobCreator $jobCreator,
        ProfileService $profileService,
        LoggerInterface $logger
    )
    {
        $this->dm = $documentManager;
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

    private function checkMultimediaObject(MultimediaObject $multimediaObject): void
    {
        // Only for objects with master
        $master = $multimediaObject->getMaster();
        if (!$master) {
            return;
        }

        // Only for non multi-stream objects
        if ($multimediaObject->isMultistream()) {
            return;
        }

        $tag = $this->dm->getRepository(Tag::class)->findOneBy(['cod' => 'PUBCHANNELS']);
        if (!$tag) {
            return;
        }

        $profileName = $master->profileName();
        if (!$profileName || !isset($this->profiles[$profileName])) {
            return;
        }
        $profile = $this->profiles[$profileName];

        // NOTE: See TTK-7482
        foreach ($tag->getChildren() as $pubChannel) {
            if ($multimediaObject->containsTag($pubChannel)) {
                if (!$master->tags()->containsTag('ENCODED_'.$pubChannel->getCod()) && !str_contains($profile['target'], (string)$pubChannel->getCod())) {
                    $master->tags()->add('ENCODED_'.$pubChannel->getCod());
                    $this->generateJobs($multimediaObject, $pubChannel->getCod());
                }
            }
        }
    }

    private function generateJobs(MultimediaObject $multimediaObject, string $pubChannelCod): void
    {
        $default_profiles = $this->profileService->getDefaultProfiles();

        if ($this->containsTrackWithProfileWithTargetTag($multimediaObject, $pubChannelCod)) {
            $this->logger->warning(sprintf(
                "JobGeneratorListener can't create a new job for multimedia object %s,".
                                        'because it already contains a track with a profile with this target (%s)',
                $multimediaObject->getId(),
                $pubChannelCod
            ));

            return;
        }

        foreach ($this->profiles as $targetProfile => $profile) {
            if (empty($profile['target'])) {
                continue;
            }

            $targets = $this->getTargets($profile['target']);

            $track = $multimediaObject->getTrackWithTag('profile:'.$targetProfile);
            if ($track) {
                $this->logger->warning(sprintf(
                    "JobGeneratorListener doesn't create a new job (%s) for multimedia object %s ".
                                            'because it already contains a track created with this profile',
                    $targetProfile,
                    $multimediaObject->getId()
                ));

                continue;
            }

            if (0 !== (is_countable($default_profiles) ? count($default_profiles) : 0)) {
                if (!isset($default_profiles[$pubChannelCod])) {
                    continue;
                }
                if (!$multimediaObject->isOnlyAudio() && !str_contains($default_profiles[$pubChannelCod]['video'], (string)$targetProfile)) {
                    continue;
                }
                if ($multimediaObject->isOnlyAudio() && !str_contains($default_profiles[$pubChannelCod]['audio'], (string)$targetProfile)) {
                    continue;
                }
            }

            if (in_array($pubChannelCod, $targets['standard'])
               && ($multimediaObject->isOnlyAudio() == $profile['audio'])) {
                if (!$multimediaObject->isOnlyAudio() && 0 != $profile['resolution_ver']) {
                    $profileAspectRatio = $profile['resolution_hor'] / $profile['resolution_ver'];
                    $multimediaObjectAspectRatio = $multimediaObject->getTrackWithTag('master')->getAspectRatio();
                    if ((1.5 > $profileAspectRatio) !== (1.5 > $multimediaObjectAspectRatio)) {
                        $this->logger->warning(sprintf(
                            "JobGeneratorListener can't create a new job (%s) for multimedia object %s using standard target, ".
                                                    'because a video profile aspect ratio(%f) is different to video aspect ratio (%f)',
                            $targetProfile,
                            $multimediaObject->getId(),
                            $profileAspectRatio,
                            $multimediaObjectAspectRatio
                        ));

                        continue;
                    }
                }

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

    /**
     * Process the target string (See test)
     * "TAGA* TAGB, TAGC*, TAGD" => array('standard' => array('TAGB', 'TAGD'), 'force' => array('TAGA', 'TAGC')).
     */
    private function getTargets($targets): array
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

    private function containsTrackWithProfileWithTargetTag(MultimediaObject $multimediaObject, $pubChannelCod): bool
    {
        foreach ($multimediaObject->getTracks() as $track) {
            $profileName = $track->profileName();
            if ($profileName && isset($this->profiles[$profileName])) {
                $targets = $this->getTargets($this->profiles[$profileName]['target']);
                if (in_array($pubChannelCod, $targets['standard'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
