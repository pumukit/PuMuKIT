<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Psr\Log\LoggerInterface;
use Pumukit\EncoderBundle\Document\Job;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class JobRender
{
    private ProfileValidator $profileValidator;
    private JobValidator $jobValidator;
    private LoggerInterface $logger;
    private string $tmpPath;

    public function __construct(ProfileValidator $profileValidator, JobValidator $jobValidator, LoggerInterface $logger, string $tmpPath)
    {
        $this->profileValidator = $profileValidator;
        $this->jobValidator = $jobValidator;
        $this->logger = $logger;
        $this->tmpPath = $tmpPath;
    }

    public function renderBat(Job $job): string
    {
        $profile = $this->profileValidator->ensureProfileExists($job->getProfile());
        $multimediaObject = $this->jobValidator->ensureMultimediaObjectExists($job);

        $vars = $job->getInitVars();

        $vars['tracks'] = [];
        $vars['tracks_audio'] = [];
        $vars['tracks_video'] = [];
        foreach ($multimediaObject->getTracks() as $track) {
            foreach ($track->tags()->toArray() as $tag) {
                $vars['tracks'][$tag] = $track->storage()->path()->path();
                if ($track->metadata()->isOnlyAudio()) {
                    $vars['tracks_audio'][$tag] = $track->storage()->path()->path();
                } else {
                    $vars['tracks_video'][$tag] = $track->storage()->path()->path();
                }
            }
        }

        $vars['properties'] = $multimediaObject->getProperties();

        $vars['input'] = $job->getPathIni();
        $vars['output'] = $job->getPathEnd();
        $vars['output_dirname'] = dirname($vars['output']);
        $vars['output_basename'] = basename($vars['output']);

        foreach (range(1, 9) as $identifier) {
            $vars['tmpfile'.$identifier] = $this->tmpPath.'/'.random_int(0, mt_getrandmax());
        }

        $loader = new ArrayLoader(['bat' => $profile['bat']]);
        $twig = new Environment($loader);

        $commandLine = $twig->render('bat', $vars);
        $this->logger->info('[renderBat] CommandLine: '.$commandLine);

        return $commandLine;
    }
}
