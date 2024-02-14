<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Psr\Log\LoggerInterface;

final class ProfileValidator
{
    private ProfileService $profileService;
    private LoggerInterface $logger;

    public function __construct(ProfileService $profileService, LoggerInterface $logger)
    {
        $this->profileService = $profileService;
        $this->logger = $logger;
    }

    public function ensureProfileExists(string $profile): array
    {
        $matchedProfile = $this->profileService->getProfile($profile);
        if (null === $matchedProfile) {
            $this->logger->error('['.__FUNCTION__.']: Cannot find given profile with name "'.$profile);

            throw new \InvalidArgumentException('Profile '.$profile.' does not exist.');
        }

        return $matchedProfile;
    }
}
