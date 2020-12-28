<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\SpecialTranslationService;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PumukitAdminExtension extends AbstractExtension
{
    private $dm;
    private $languages;
    private $profileService;
    private $translator;
    private $router;
    private $countMmobjsByStatus;
    private $countMmobjsWithTag;
    private $mmobjService;
    private $specialTranslationService;
    private $eventService;

    public function __construct(ProfileService $profileService, DocumentManager $documentManager, TranslatorInterface $translator, RouterInterface $router, MultimediaObjectService $mmobjService, SpecialTranslationService $specialTranslationService, EmbeddedEventSessionService $eventService)
    {
        $this->dm = $documentManager;
        $this->languages = Languages::getNames();
        $this->profileService = $profileService;
        $this->translator = $translator;
        $this->router = $router;
        $this->mmobjService = $mmobjService;
        $this->specialTranslationService = $specialTranslationService;
        $this->eventService = $eventService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', [$this, 'getBasename']),
            new TwigFilter('profile', [$this, 'getProfile']),
            new TwigFilter('display', [$this, 'getDisplay']),
            new TwigFilter('duration_string', [$this, 'getDurationString']),
            new TwigFilter('language_name', [$this, 'getLanguageName']),
            new TwigFilter('status_icon', [$this, 'getStatusIcon']),
            new TwigFilter('status_text', [$this, 'getStatusText']),
            new TwigFilter('series_icon', [$this, 'getSeriesIcon']),
            new TwigFilter('series_text', [$this, 'getSeriesText']),
            new TwigFilter('profile_width', [$this, 'getProfileWidth']),
            new TwigFilter('profile_height', [$this, 'getProfileHeight']),
            new TwigFilter('series_announce_icon', [$this, 'getSeriesAnnounceIcon']),
            new TwigFilter('series_announce_text', [$this, 'getSeriesAnnounceText']),
            new TwigFilter('mms_announce_icon', [$this, 'getMmsAnnounceIcon']),
            new TwigFilter('mms_announce_text', [$this, 'getMmsAnnounceText']),
            new TwigFilter('filter_profiles', [$this, 'filterProfiles']),
            new TwigFilter('count_multimedia_objects', [$this, 'countMultimediaObjects']),
            new TwigFilter('next_session_event', [$this, 'getNextEventSession']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('php_upload_max_filesize', [$this, 'getPhpUploadMaxFileSize']),
            new TwigFunction('path_exists', [$this, 'existsRoute']),
            new TwigFunction('is_playable_on_playlist', [$this, 'isPlayableOnPlaylist']),
            new TwigFunction('is_mmobj_owner', [$this, 'isUserOwner']),
            new TwigFunction('broadcast_description', [$this, 'getBroadcastDescription']),
            new TwigFunction('is_naked', [$this, 'isNaked'], ['needs_environment' => true]),
            new TwigFunction('trans_i18n_broadcast', [$this, 'getI18nEmbeddedBroadcast']),
            new TwigFunction('date_from_mongo_id', [$this, 'getDateFromMongoId']),
            new TwigFunction('default_poster', [$this, 'getDefaultPoster']),
            new TwigFunction('sort_roles', [$this, 'getSortRoles']),
            new TwigFunction('status_string_text_by_value', [$this, 'getStatusTextByValue']),
            new TwigFunction('role_string_text_by_value', [$this, 'getRoleTextByValue']),
        ];
    }

    public function getBasename(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return basename($path);
    }

    public function getProfile(array $tags)
    {
        $profile = '';

        foreach ($tags as $tag) {
            if (false !== strpos($tag, 'profile:')) {
                return substr($tag, strlen('profile:'), strlen($tag) - 1);
            }
        }

        return $profile;
    }

    public function existsRoute(string $name): bool
    {
        return null !== $this->router->getRouteCollection()->get($name);
    }

    public function getDisplay(string $profileName)
    {
        $display = false;
        $profile = $this->profileService->getProfile($profileName);

        if (null !== $profile) {
            $display = $profile['display'];
        }

        return $display;
    }

    public function getDurationString(int $duration): string
    {
        return gmdate('H:i:s', $duration);
    }

    public function getLanguageName(string $code, bool $translate = true): string
    {
        $addonLanguages = CustomLanguageType::$addonLanguages;

        if ('zh-CN' === $code) {
            return '简体中文';
        }
        if ('zh-TW' === $code) {
            return '繁體中文';
        }

        if (isset($this->languages[$code])) {
            $name = $translate ?
                  $this->languages[$code] :
                  Languages::getName($code);

            return ucfirst($name);
        }
        if (isset($addonLanguages[$code])) {
            $name = $addonLanguages[$code];

            if ($translate) {
                $name = $this->translator->trans($name);
            }

            return ucfirst($name);
        }

        return $code;
    }

    public function getStatusIcon(int $status): string
    {
        $iconClass = 'mdi-alert-warning';

        switch ($status) {
            case MultimediaObject::STATUS_PUBLISHED:
                $iconClass = 'mdi-device-signal-wifi-4-bar';

                break;

            case MultimediaObject::STATUS_HIDDEN:
                $iconClass = 'mdi-device-signal-wifi-0-bar';

                break;

            case MultimediaObject::STATUS_BLOCKED:
                $iconClass = 'mdi-device-wifi-lock';

                break;
        }

        return $iconClass;
    }

    public function getStatusText($param): string
    {
        $iconText = 'New';

        $status = $param instanceof MultimediaObject ? $param->getStatus() : $param;

        switch ($status) {
            case MultimediaObject::STATUS_PUBLISHED:
                if ($param instanceof MultimediaObject && $param->containsTagWithCod('PUCHWEBTV')) {
                    $iconText = $this->translator->trans('Published: is listed in the Series and can be played with published URL');
                } else {
                    $iconText = $this->translator->trans('Published');
                }

                break;

            case MultimediaObject::STATUS_HIDDEN:
                $iconText = $this->translator->trans('Hidden: is not listed in the Series but can be played with magic URL');

                break;

            case MultimediaObject::STATUS_BLOCKED:
                $iconText = $this->translator->trans('Blocked: cannot be accessed outside the back-end');

                break;
        }

        return $iconText;
    }

    public function getStatusTextByValue(int $status): string
    {
        return MultimediaObject::$statusTexts[$status];
    }

    public function getRoleTextByValue(string $roleCode): string
    {
        $role = $this->dm->getRepository(Role::class)->findOneBy(['cod' => $roleCode]);
        if (!$role) {
            throw new \Exception('Role not found');
        }

        return $role->getName();
    }

    public function getSeriesIcon(Series $series)
    {
        [$mmobjsPublished, $mmobjsHidden, $mmobjsBlocked] = $this->countMmobjsByStatus($series);

        $iconClass = 'mdi-alert-warning';

        if ((0 === $mmobjsPublished) && (0 === $mmobjsHidden) && (0 === $mmobjsBlocked)) {
            $iconClass = 'mdi-device-signal-wifi-off pumukit-none';
        } elseif (($mmobjsPublished > $mmobjsHidden) && ($mmobjsPublished > $mmobjsBlocked)) {
            $iconClass = 'mdi-device-signal-wifi-4-bar pumukit-published';
        } elseif (($mmobjsPublished === $mmobjsHidden) && ($mmobjsPublished > $mmobjsBlocked)) {
            $iconClass = 'mdi-device-signal-wifi-0-bar pumukit-hidden-published';
        } elseif (($mmobjsHidden > $mmobjsPublished) && ($mmobjsHidden > $mmobjsBlocked)) {
            $iconClass = 'mdi-device-signal-wifi-0-bar pumukit-hidden';
        } elseif (($mmobjsPublished === $mmobjsBlocked) && ($mmobjsPublished > $mmobjsHidden)) {
            $iconClass = 'mdi-device-wifi-lock pumukit-blocked-published';
        } elseif (($mmobjsBlocked === $mmobjsHidden) && ($mmobjsBlocked > $mmobjsPublished)) {
            $iconClass = 'mdi-device-wifi-lock pumukit-blocked-hidden';
        } elseif (($mmobjsPublished === $mmobjsBlocked) && ($mmobjsPublished === $mmobjsHidden)) {
            $iconClass = 'mdi-device-wifi-lock pumukit-blocked-hidden-published';
        } elseif (($mmobjsBlocked > $mmobjsPublished) && ($mmobjsBlocked > $mmobjsHidden)) {
            $iconClass = 'mdi-device-wifi-lock pumukit-blocked';
        }

        if ($series->isHide()) {
            $iconClass .= ' pumukit-series-hidden';
        }

        return $iconClass;
    }

    public function getSeriesText(Series $series): string
    {
        [$mmobjsPublished, $mmobjsHidden, $mmobjsBlocked] = $this->countMmobjsByStatus($series);

        return sprintf(
            "%s: \n %s: %d,\n%s: %d,\n%s: %d\n",
            $this->translator->trans('Multimedia Objects'),
            $this->translator->trans('i18n.multiple.Published'),
            $mmobjsPublished,
            $this->translator->trans('i18n.multiple.Hidden'),
            $mmobjsHidden,
            $this->translator->trans('i18n.multiple.Blocked'),
            $mmobjsBlocked
        );
    }

    public function getProfileWidth(array $tags): string
    {
        $profileName = $this->getProfileFromTags($tags);
        $profile = $this->profileService->getProfile($profileName);
        if (null !== $profile) {
            return $profile['resolution_hor'];
        }

        return '0';
    }

    public function getProfileHeight(array $tags): string
    {
        $profileName = $this->getProfileFromTags($tags);
        $profile = $this->profileService->getProfile($profileName);
        if (null !== $profile) {
            return $profile['resolution_ver'];
        }

        return '0';
    }

    public function getSeriesAnnounceIcon(Series $series): string
    {
        $icon = 'mdi-action-done pumukit-transparent';

        if ($series->getAnnounce()) {
            return 'mdi-action-spellcheck pumukit-series-announce';
        }

        return $icon;
    }

    public function getSeriesAnnounceText(Series $series): string
    {
        $text = '';

        if ($series->getAnnounce()) {
            return $this->translator->trans('This Series is announced');
        }

        return $text;
    }

    public function getMmsAnnounceIcon(Series $series): string
    {
        $icon = 'mdi-action-done pumukit-transparent';

        $count = $this->countMmobjsWithTag($series, 'PUDENEW');

        if ($count > 0) {
            return 'mdi-action-spellcheck pumukit-mm-announce';
        }

        return $icon;
    }

    public function getMmsAnnounceText(Series $series): string
    {
        $text = '';

        $count = $this->countMmobjsWithTag($series, 'PUDENEW');

        if ($count > 0) {
            return 'This Series has '.$count.' announced Multimedia Object(s)';
        }

        return $text;
    }

    public function getPhpUploadMaxFileSize(): string
    {
        return ini_get('upload_max_filesize').'B';
    }

    public function filterProfiles($profiles, $onlyAudio): array
    {
        return array_filter($profiles, static function ($elem) use ($onlyAudio) {
            return !$onlyAudio || $elem['audio'];
        });
    }

    public function countMultimediaObjects(Series $series): int
    {
        return $this->dm->getRepository(MultimediaObject::class)->countInSeries($series);
    }

    public function getBroadcastDescription($broadcastType, $template, bool $isLive = false): string
    {
        $description = '';

        $changeWord = 'multimedia object';
        if ($isLive) {
            $changeWord = 'live event';
        }
        if ((EmbeddedBroadcast::TYPE_PUBLIC === $broadcastType) && $template) {
            $description = $this->translator->trans('Any Internet user can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_PUBLIC === $broadcastType) {
            $description = $this->translator->trans("Any Internet user can play this {$changeWord}");
        } elseif ((EmbeddedBroadcast::TYPE_PASSWORD === $broadcastType) && $template) {
            $description = $this->translator->trans('Only users with the defined password can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_PASSWORD === $broadcastType) {
            $description = $this->translator->trans("Only users with the defined password can play this {$changeWord}");
        } elseif ((EmbeddedBroadcast::TYPE_LOGIN === $broadcastType) && $template) {
            $description = $this->translator->trans('Only logged in users in the system can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_LOGIN === $broadcastType) {
            $description = $this->translator->trans("Only logged in users in the system can play this {$changeWord}");
        } elseif ((EmbeddedBroadcast::TYPE_GROUPS === $broadcastType) && $template) {
            $description = $this->translator->trans('Only users in the selected Groups can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $broadcastType) {
            $description = $this->translator->trans("Only users in the selected Groups can play this {$changeWord}");
        }

        return $description;
    }

    public function isPlayableOnPlaylist(MultimediaObject $multimediaObject): bool
    {
        return $this->mmobjService->isPlayableOnPlaylist($multimediaObject);
    }

    public function isUserOwner(User $user, MultimediaObject $multimediaObject): bool
    {
        return $this->mmobjService->isUserOwner($user, $multimediaObject);
    }

    public function isNaked(Environment $env)
    {
        if (isset($env->getGlobals()['app'])) {
            return $env->getGlobals()['app']->getRequest()->attributes->get('nakedbackoffice', false);
        }

        return false;
    }

    public function getI18nEmbeddedBroadcast(EmbeddedBroadcast $embeddedBroadcast, ?string $locale = 'en'): string
    {
        return $this->specialTranslationService->getI18nEmbeddedBroadcast($embeddedBroadcast, $locale);
    }

    public function getDateFromMongoId(MultimediaObject $multimediaObject)
    {
        $id = new ObjectId($multimediaObject->getId());

        return $id->getTimestamp();
    }

    public function getNextEventSession(Multimediaobject $multimediaObject)
    {
        $now = new \DateTime();
        $now = $now->getTimestamp();
        $aSessions = [];
        $event = $multimediaObject->getEmbeddedEvent();
        foreach ($event->getEmbeddedEventSession() as $session) {
            $sessionStart = clone $session->getStart();
            $sessionEnds = $sessionStart->add(new \DateInterval('PT'.$session->getDuration().'S'));
            if ($session->getStart()->getTimestamp() > $now) {
                $aSessions[$session->getStart()->getTimestamp()][] = $session;
            } elseif (($session->getStart()->getTimestamp() < $now) && ($sessionEnds->getTimestamp() > $now)) {
                $aSessions[$session->getStart()->getTimestamp()][] = $session;
            }
        }
        if (!empty($aSessions)) {
            ksort($aSessions);

            return array_shift($aSessions);
        }

        return false;
    }

    public function getDefaultPoster(): string
    {
        return $this->eventService->getDefaultPoster();
    }

    public function getSortRoles(Multimediaobject $multimediaObject, bool $display = true): array
    {
        static $rolesCached = [];

        if (isset($rolesCached[$display])) {
            $roles = $rolesCached[$display];
        } else {
            $roles = $this->dm->getRepository(Role::class)->findBy(['display' => $display], ['rank' => 1]);
            $rolesCached[$display] = $roles;
        }

        $aRoles = [];
        foreach ($roles as $role) {
            $embeddedRole = $multimediaObject->getEmbeddedRole($role);
            if ($embeddedRole && 0 !== count($embeddedRole->getPeople())) {
                $aRoles[] = $embeddedRole;
            }
        }

        return $aRoles;
    }

    private function getProfileFromTags(array $tags): string
    {
        $profile = '';

        foreach ($tags as $tag) {
            if (false !== strpos($tag, 'profile:')) {
                return substr($tag, strlen('profile:'), strlen($tag) - 1);
            }
        }

        return $profile;
    }

    private function countMmobjsByStatus(Series $series)
    {
        if (isset($this->countMmobjsByStatus[$series->getId()])) {
            return $this->countMmobjsByStatus[$series->getId()];
        }
        $mmobjsPublished = 0;
        $mmobjsHidden = 0;
        $mmobjsBlocked = 0;

        $seriesColl = $this->dm->getDocumentCollection(MultimediaObject::class);
        $aggrPipe = [
            ['$match' => ['series' => new ObjectId($series->getId())]],
            ['$group' => ['_id' => '$status',
                'count' => ['$sum' => 1], ]],
        ];
        $mmobjCounts = iterator_to_array($seriesColl->aggregate($aggrPipe, ['cursor' => []]));

        foreach ($mmobjCounts as $mmobjCount) {
            switch ($mmobjCount['_id']) {
                case MultimediaObject::STATUS_PUBLISHED:
                    $mmobjsPublished = $mmobjCount['count'];

                    break;

                case MultimediaObject::STATUS_HIDDEN:
                    $mmobjsHidden = $mmobjCount['count'];

                    break;

                case MultimediaObject::STATUS_BLOCKED:
                    $mmobjsBlocked = $mmobjCount['count'];

                    break;
            }
        }

        $result = [$mmobjsPublished, $mmobjsHidden, $mmobjsBlocked];

        return $this->countMmobjsByStatus[$series->getId()] = $result;
    }

    private function countMmobjsWithTag(Series $series, string $tagCod)
    {
        if (isset($this->countMmobjsWithTag[$series->getId()][$tagCod])) {
            return $this->countMmobjsWithTag[$series->getId()][$tagCod];
        }
        $repoSeries = $this->dm->getRepository(MultimediaObject::class);
        $qb = $repoSeries->createStandardQueryBuilder()->field('series')->equals(new ObjectId($series->getId()))->field('tags.cod')->equals('PUDENEW');
        $count = $qb->count()->getQuery()->execute();

        $this->countMmobjsWithTag[$series->getId()][$tagCod] = $count;

        return $count;
    }
}
