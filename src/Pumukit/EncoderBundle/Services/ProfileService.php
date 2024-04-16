<?php

declare(strict_types=1);

namespace Pumukit\EncoderBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class ProfileService
{
    public const STREAMSERVER_STORE = 'store';
    public const STREAMSERVER_DOWNLOAD = 'download';
    public const STREAMSERVER_WMV = 'wmv';
    public const STREAMSERVER_FMS = 'fms';
    public const STREAMSERVER_RED5 = 'red5';
    private $dm;
    private $profiles;
    private $default_profiles;

    /**
     * Constructor.
     */
    public function __construct(array $profiles, DocumentManager $documentManager, array $default_profiles = [])
    {
        $this->dm = $documentManager;
        $this->profiles = $profiles;
        $this->default_profiles = $default_profiles;
    }

    /**
     * Get available profiles
     * See #7482.
     *
     * @param bool|null $display if not null used to filter
     * @param bool|null $wizard  if not null used to filter
     * @param bool|null $master  if not null used to filter
     *
     * @return array filtered profiles
     */
    public function getProfiles($display = null, $wizard = null, $master = null)
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

    /**
     * Get available profiles
     * See #7482.
     *
     * @param array|string $tags Tags used to filter profiles
     *
     * @return array filtered profiles
     */
    public function getProfilesByTags($tags)
    {
        $tags = is_array($tags) ? $tags : [$tags];

        return array_filter($this->profiles, function ($profile) use ($tags) {
            return 0 == count(array_diff($tags, array_filter(preg_split('/[,\s]+/', $profile['tags']))));
        });
    }

    /**
     * Get master profiles.
     *
     * @param bool $master
     *
     * @return array $profiles only master if true, only not master if false
     */
    public function getMasterProfiles($master)
    {
        return $this->getProfiles(null, null, $master);
    }

    /**
     * Get the default master profile.
     * See #7482.
     */
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

    /**
     * Get dir out info from streamserver of profiles.
     */
    public function getDirOutInfo()
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

    /**
     * Validate Profiles directories out.
     * Note BC. @deprecated in next version.
     */
    public function validateProfilesDirOut()
    {
        static::validateProfilesDir($this->profiles);
    }

    /**
     * Validate Profiles directories out.
     */
    public static function validateProfilesDir(array $profiles)
    {
        foreach ($profiles as $profile) {
            $dirOut = realpath($profile['streamserver']['dir_out']);
            if (!$dirOut) {
                throw new \InvalidArgumentException("The path '".$profile['streamserver']['dir_out']."' for dir_out of the streamserver '".$profile['streamserver']['name']."' doesn't exist.");
            }
        }
    }

    /**
     * Get target default profiles.
     */
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
}
