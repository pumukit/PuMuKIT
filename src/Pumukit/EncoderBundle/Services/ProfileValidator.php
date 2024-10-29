<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\MimeTypes;

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

    public function searchBestProfileForFile(string $genericProfile, string $pathFile): string
    {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($pathFile);

        if (str_contains($mimeType, 'image/')) {
            return 'master_copy';
        }

        if (str_contains($mimeType, 'application/')) {
            return 'master_copy';
        }

        if (str_contains($mimeType, 'audio/')) {
            if ('broadcastable' === $genericProfile) {
                return 'audio_broadcastable';
            }

            return 'master_copy';
        }

        if (str_contains($mimeType, 'video/')) {
            if ('master_copy' === $genericProfile) {
                return 'master_copy';
            }
            if ('master_encoded' === $genericProfile) {
                return 'video_master_encoded';
            }
            if ('broadcastable' === $genericProfile) {
                return 'video_master_broadcastable';
            }
        }

        return 'master_copy';
    }
}
