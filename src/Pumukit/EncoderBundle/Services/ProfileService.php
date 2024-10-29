<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Pumukit\CoreBundle\Utils\ImageRawUtils;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class ProfileService
{
    public const STREAMSERVER_STORE = 'store';
    public const STREAMSERVER_DOWNLOAD = 'download';
    public const STREAMSERVER_WMV = 'wmv';
    public const STREAMSERVER_FMS = 'fms';
    public const STREAMSERVER_RED5 = 'red5';
    private $profiles;
    private $default_profiles;

    public function __construct(array $profiles, array $default_profiles = [])
    {
        $this->profiles = $profiles;
        $this->default_profiles = $default_profiles;
    }

    public function getProfiles($display = null, $wizard = null, $master = null): array
    {
        if (null === $display && null === $wizard && null === $master) {
            return $this->profiles;
        }

        return array_filter($this->profiles, function ($profile) use ($display, $wizard, $master) {
            return (null === $display || $profile['display'] === $display)
                    && (null === $wizard || $profile['wizard'] === $wizard)
                    && (null === $master || $profile['master'] === $master);
        });
    }

    public function getProfilesByTags($tags): array
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return array_filter($this->profiles, function ($profile) use ($tags) {
            return 0 == count(array_diff($tags, array_filter(preg_split('/[,\s]+/', $profile['tags']))));
        });
    }

    public function getMasterProfiles($master): array
    {
        return $this->getProfiles(null, null, $master);
    }

    public function getDefaultMasterProfile()
    {
        $masterProfiles = $this->getMasterProfiles(true);

        $tags = ['copy'];
        $masterNotCopyProfiles = array_filter($masterProfiles, function ($profile) use ($tags) {
            if (isset($profile['tags'])) {
                return 0 != count(array_diff($tags, array_filter(preg_split('/[,\s]+/', $profile['tags']))));
            }
        });

        if ($masterNotCopyProfiles) {
            return array_keys($masterNotCopyProfiles)[0];
        }

        // Use copy master profiles if not-copy master profile doesn't exists
        if ($masterProfiles) {
            return array_keys($masterProfiles)[0];
        }

        return null;
    }

    public function getProfile($profile)
    {
        if (isset($this->profiles[$profile])) {
            return $this->profiles[$profile];
        }

        return null;
    }

    public function getDirOutInfo(): array
    {
        $f = function ($e) {
            return $e['streamserver']['dir_out'];
        };
        $shares = array_unique(array_values(array_map($f, $this->profiles)));

        return array_map(function ($e) {
            return ['dir' => $e,
                'free' => disk_free_space($e),
                'total' => disk_total_space($e), ];
        }, $shares);
    }

    public static function validateProfilesDir(array $profiles): void
    {
        foreach ($profiles as $profile) {
            $dirOut = realpath($profile['streamserver']['dir_out']);
            if (!$dirOut) {
                throw new \InvalidArgumentException("The path '".$profile['streamserver']['dir_out']."' for dir_out of the streamserver '".$profile['streamserver']['name']."' doesn't exist.");
            }
        }
    }

    public function getDefaultProfiles(): array
    {
        if (null === $this->default_profiles) {
            throw new \InvalidArgumentException('No target default profiles.');
        }

        return $this->default_profiles;
    }

    public function defaultProfilesByMultimediaObjectAndPubChannel(MultimediaObject $multimediaObject, Tag $tag)
    {
        if (null === $this->default_profiles) {
            throw new \InvalidArgumentException('No target default profiles.');
        }

        return match ($multimediaObject->getType()) {
            MultimediaObject::TYPE_VIDEO => $this->default_profiles[$tag->getCod()]['video'],
            MultimediaObject::TYPE_AUDIO => $this->default_profiles[$tag->getCod()]['audio'],
            MultimediaObject::TYPE_IMAGE => $this->default_profiles[$tag->getCod()]['image'],
            MultimediaObject::TYPE_DOCUMENT => $this->default_profiles[$tag->getCod()]['document'],
            default => throw new \InvalidArgumentException('No target default profiles.'),
        };
    }

    public function generateProfileTag(string $profileName): string
    {
        return 'profile:'.$profileName;
    }

    public function filterProfilesByPubChannel(Tag $tag): array
    {
        $pubChannelCod = $tag->getCod();

        return array_filter($this->profiles, function ($profile) use ($pubChannelCod) {
            return isset($profile['target']) && str_contains($profile['target'], $pubChannelCod);
        });
    }

    public function filterProfilesByPubChannelAndType(Tag $tag, MultimediaObject $multimediaObject): array
    {
        $profilesFilteredByTag = $this->filterProfilesByPubChannel($tag);
        $profilesFilteredByType = $this->filterProfilesByType($multimediaObject);

        $filteredProfiles = array_intersect_key($profilesFilteredByTag, $profilesFilteredByType);
        $pubChannelCod = $tag->getCod();

        return array_filter($filteredProfiles, function ($profile) use ($pubChannelCod) {
            return isset($profile['target']) && str_contains($profile['target'], $pubChannelCod);
        });
    }

    public function filterProfilesByType(MultimediaObject $multimediaObject): array
    {
        return match ($multimediaObject->getType()) {
            MultimediaObject::TYPE_VIDEO => $this->videoProfiles(),
            MultimediaObject::TYPE_AUDIO => $this->audioProfiles(),
            MultimediaObject::TYPE_IMAGE => $this->imageProfiles($multimediaObject),
            MultimediaObject::TYPE_DOCUMENT => $this->documentProfiles(),
            default => throw new \InvalidArgumentException('No target default profiles.'),
        };
    }

    public function imageProfiles(MultimediaObject $multimediaObject): array
    {
        $master = $multimediaObject->getMaster();
        if (!$master) {
            throw new \InvalidArgumentException('The multimedia object has no master.');
        }

        $path = $master->storage()->path();
        if (ImageRawUtils::isRawImage($path)) {
            return $this->imageRawProfiles();
        }

        return $this->imageGenericProfiles();
    }

    public function documentProfiles(): array
    {
        return array_filter($this->profiles, function ($profile) { return isset($profile['document']) && true === $profile['document'] && false === $profile['master']; });
    }

    public function audioProfiles(): array
    {
        return array_filter($this->profiles, function ($profile) { return isset($profile['audio']) && true === $profile['audio'] && false === $profile['master']; });
    }

    public function videoProfiles(): array
    {
        return array_filter($this->profiles, function ($profile) {
            return (!isset($profile['document']) || false === $profile['document']) && (!isset($profile['image']) || false === $profile['image']) && false === $profile['master'];
        });
    }

    private function imageRawProfiles(): array
    {
        return array_filter($this->profiles, function ($profile) { return isset($profile['image']) && true === $profile['image'] && false === $profile['master'] && str_contains($profile['tags'], 'raw'); });
    }

    private function imageGenericProfiles(): array
    {
        return array_filter($this->profiles, function ($profile) { return isset($profile['image']) && true === $profile['image'] && false === $profile['master'] && !str_contains($profile['tags'], 'raw'); });
    }
}
