<?php

namespace Pumukit\NewAdminBundle\Twig;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\NewAdminBundle\Form\Type\Base\CustomLanguageType;
use Pumukit\SchemaBundle\Services\MultimediaObjectService;
use Pumukit\SchemaBundle\Services\SpecialTranslationService;
use Pumukit\SchemaBundle\Services\EmbeddedEventSessionService;

class PumukitAdminExtension extends \Twig_Extension
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

    /**
     * Constructor.
     */
    public function __construct(ProfileService $profileService, DocumentManager $documentManager, TranslatorInterface $translator, RouterInterface $router, MultimediaObjectService $mmobjService, SpecialTranslationService $specialTranslationService, EmbeddedEventSessionService $eventService)
    {
        $this->dm = $documentManager;
        $this->languages = Intl::getLanguageBundle()->getLanguageNames();
        $this->profileService = $profileService;
        $this->translator = $translator;
        $this->router = $router;
        $this->mmobjService = $mmobjService;
        $this->specialTranslationService = $specialTranslationService;
        $this->eventService = $eventService;
    }

    /**
     * Get name.
     */
    public function getName()
    {
        return 'pumukitadmin_extension';
    }

    /**
     * Get filters.
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('basename', array($this, 'getBasename')),
            new \Twig_SimpleFilter('profile', array($this, 'getProfile')),
            new \Twig_SimpleFilter('display', array($this, 'getDisplay')),
            new \Twig_SimpleFilter('duration_string', array($this, 'getDurationString')),
            new \Twig_SimpleFilter('language_name', array($this, 'getLanguageName')),
            new \Twig_SimpleFilter('status_icon', array($this, 'getStatusIcon')),
            new \Twig_SimpleFilter('status_text', array($this, 'getStatusText')),
            new \Twig_SimpleFilter('series_icon', array($this, 'getSeriesIcon')),
            new \Twig_SimpleFilter('series_text', array($this, 'getSeriesText')),
            new \Twig_SimpleFilter('profile_width', array($this, 'getProfileWidth')),
            new \Twig_SimpleFilter('profile_height', array($this, 'getProfileHeight')),
            new \Twig_SimpleFilter('series_announce_icon', array($this, 'getSeriesAnnounceIcon')),
            new \Twig_SimpleFilter('series_announce_text', array($this, 'getSeriesAnnounceText')),
            new \Twig_SimpleFilter('mms_announce_icon', array($this, 'getMmsAnnounceIcon')),
            new \Twig_SimpleFilter('mms_announce_text', array($this, 'getMmsAnnounceText')),
            new \Twig_SimpleFilter('filter_profiles', array($this, 'filterProfiles')),
            new \Twig_SimpleFilter('count_multimedia_objects', array($this, 'countMultimediaObjects')),
            new \Twig_SimpleFilter('next_session_event', array($this, 'getNextEventSession')),
        );
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('php_upload_max_filesize', array($this, 'getPhpUploadMaxFilesize')),
            new \Twig_SimpleFunction('path_exists', array($this, 'existsRoute')),
            new \Twig_SimpleFunction('is_playable_on_playlist', array($this, 'isPlayableOnPlaylist')),
            new \Twig_SimpleFunction('is_mmobj_owner', array($this, 'isUserOwner')),
            new \Twig_SimpleFunction('broadcast_description', array($this, 'getBroadcastDescription')),
            new \Twig_SimpleFunction('is_naked', array($this, 'isNaked'), array('needs_environment' => true)),
            new \Twig_SimpleFunction('trans_i18n_broadcast', array($this, 'getI18nEmbeddedBroadcast')),
            new \Twig_SimpleFunction('date_from_mongo_id', array($this, 'getDateFromMongoId')),
            new \Twig_SimpleFunction('default_poster', array($this, 'getDefaultPoster')),
            new \Twig_SimpleFunction('sort_roles', array($this, 'getSortRoles')),
        );
    }

    /**
     * Get basename.
     *
     * @param string $path
     *
     * @return string
     */
    public function getBasename($path)
    {
        return basename($path);
    }

    /**
     * Get profile.
     *
     * @param Collection $tags
     *
     * @return string
     */
    public function getProfile($tags)
    {
        $profile = '';

        foreach ($tags as $tag) {
            if (false !== strpos($tag, 'profile:')) {
                return substr($tag, strlen('profile:'), strlen($tag) - 1);
            }
        }

        return $profile;
    }

    /**
     * Check if a route exists.
     *
     * @param string $name route name
     *
     * @return boolena
     */
    public function existsRoute($name)
    {
        return null !== $this->router->getRouteCollection()->get($name);
    }

    /**
     * Get display.
     *
     * @param string $profileName
     *
     * @return string
     */
    public function getDisplay($profileName)
    {
        $display = false;
        $profile = $this->profileService->getProfile($profileName);

        if (null !== $profile) {
            $display = $profile['display'];
        }

        return $display;
    }

    /**
     * Get duration string.
     *
     * @param int $duration
     *
     * @return string
     */
    public function getDurationString($duration)
    {
        return gmdate('H:i:s', $duration);
    }

    /**
     * Get language name.
     *
     * @param string $code      language ISO 639 code
     * @param bool   $translate Translate the language name or get it in their language. True by default
     *
     * @return string
     */
    public function getLanguageName($code, $translate = true)
    {
        $addonLanguages = CustomLanguageType::$addonLanguages;

        if (isset($this->languages[$code])) {
            $name = $translate ?
                  $this->languages[$code] :
                  Intl::getLanguageBundle()->getLanguageName($code, null, $code);

            return ucfirst($name);
        } elseif (isset($addonLanguages[$code])) {
            $name = $addonLanguages[$code];

            if ($translate) {
                $name = $this->translator->trans($name);
            }

            return ucfirst($name);
        }

        return $code;
    }

    /**
     * Get status icon.
     *
     * @param int $status
     *
     * @return string
     */
    public function getStatusIcon($status)
    {
        $iconClass = 'mdi-alert-warning';

        switch ($status) {
            case MultimediaObject::STATUS_PUBLISHED:
                $iconClass = 'mdi-device-signal-wifi-4-bar';
                break;
            case MultimediaObject::STATUS_HIDE:
                $iconClass = 'mdi-device-signal-wifi-0-bar';
                break;
            case MultimediaObject::STATUS_BLOQ:
                $iconClass = 'mdi-device-wifi-lock';
                break;
        }

        return $iconClass;
    }

    /**
     * Get status text.
     *
     * @param int $status
     *
     * @return string
     */
    public function getStatusText($status)
    {
        $iconText = 'New';

        switch ($status) {
            case MultimediaObject::STATUS_PUBLISHED:
                $iconText = $this->translator->trans('Published: is listed in the Series and can be played with published URL');
                break;
            case MultimediaObject::STATUS_HIDE:
                $iconText = $this->translator->trans('Hidden: is not listed in the Series but can be played with magic URL');
                break;
            case MultimediaObject::STATUS_BLOQ:
                $iconText = $this->translator->trans('Blocked: cannot be accessed outside the back-end');
                break;
        }

        return $iconText;
    }

    /**
     * Get series icon.
     *
     * @param string $series
     *
     * @return string
     */
    public function getSeriesIcon($series)
    {
        list($mmobjsPublished, $mmobjsHidden, $mmobjsBlocked) = $this->countMmobjsByStatus($series);

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

    /**
     * Get series text.
     *
     * @param int $series
     *
     * @return string
     */
    public function getSeriesText($series)
    {
        list($mmobjsPublished, $mmobjsHidden, $mmobjsBlocked) = $this->countMmobjsByStatus($series);

        $iconText = sprintf(
            "%s: \n %s: %d,\n%s: %d,\n%s: %d\n",
            $this->translator->trans('Multimedia Objects'),
            $this->translator->trans('i18n.multiple.Published'),
            $mmobjsPublished,
            $this->translator->trans('i18n.multiple.Hidden'),
            $mmobjsHidden,
            $this->translator->trans('i18n.multiple.Blocked'),
            $mmobjsBlocked
        );

        return $iconText;
    }

    /**
     * Get track profile width resolution.
     *
     * @param Collection $tags
     *
     * @return string
     */
    public function getProfileWidth($tags)
    {
        $profileName = $this->getProfileFromTags($tags);
        $profile = $this->profileService->getProfile($profileName);
        if (null !== $profile) {
            return $profile['resolution_hor'];
        }

        return '0';
    }

    /**
     * Get track profile height resolution.
     *
     * @param Collection $tags
     *
     * @return string
     */
    public function getProfileHeight($tags)
    {
        $profileName = $this->getProfileFromTags($tags);
        $profile = $this->profileService->getProfile($profileName);
        if (null !== $profile) {
            return $profile['resolution_ver'];
        }

        return '0';
    }

    /**
     * Get announce icon of Series
     * and MultimediaObjects inside of it.
     *
     * @param Series $series
     *
     * @return string $icon
     */
    public function getSeriesAnnounceIcon($series)
    {
        $icon = 'mdi-action-done pumukit-transparent';

        if ($series->getAnnounce()) {
            return 'mdi-action-spellcheck pumukit-series-announce';
        }

        return $icon;
    }

    /**
     * Get announce text of Series
     * and MultimediaObjects inside of it.
     *
     * @param Series $series
     *
     * @return string $text
     */
    public function getSeriesAnnounceText($series)
    {
        $text = '';

        if ($series->getAnnounce()) {
            return $this->translator->trans('This Series is announced');
        }

        return $text;
    }

    /**
     * Get announce icon of Multimedia Objects in Series
     * and MultimediaObjects inside of it.
     *
     * @param Series $series
     *
     * @return string $icon
     */
    public function getMmsAnnounceIcon($series)
    {
        $icon = 'mdi-action-done pumukit-transparent';

        $count = $this->countMmobjsWithTag($series, 'PUDENEW');

        if ($count > 0) {
            return 'mdi-action-spellcheck pumukit-mm-announce';
        }

        return $icon;
    }

    /**
     * Get announce text of Multimedia Objects in Series
     * and MultimediaObjects inside of it.
     *
     * @param Series $series
     *
     * @return string $text
     */
    public function getMmsAnnounceText($series)
    {
        $text = '';

        $count = $this->countMmobjsWithTag($series, 'PUDENEW');

        if ($count > 0) {
            return 'This Series has '.$count.' announced Multimedia Object(s)';
        }

        return $text;
    }

    /**
     * Get php upload max filesize.
     *
     * @return string
     */
    public function getPhpUploadMaxFilesize()
    {
        return ini_get('upload_max_filesize').'B';
    }

    /**
     * Get profile.
     *
     * @param Collection $tags
     *
     * @return string
     */
    private function getProfileFromTags($tags)
    {
        $profile = '';

        foreach ($tags as $tag) {
            if (false !== strpos($tag, 'profile:')) {
                return substr($tag, strlen('profile:'), strlen($tag) - 1);
            }
        }

        return $profile;
    }

    /**
     * Get duration in minutes
     * Returns duration file in minutes.
     *
     * @return int minutes
     */
    private function getDurationInMinutes($duration)
    {
        return floor($duration / 60);
    }

    /**
     * Get duration in seconds
     * Returns duration file in seconds.
     *
     * @return int seconds
     */
    private function getDurationInSeconds($duration)
    {
        $aux = $duration % 60;
        if ($aux < 10) {
            $aux = '0'.$aux;
        }

        return $aux;
    }

    /**
     * Filter profiles to show only audio profiles.
     *
     * @return array
     */
    public function filterProfiles($profiles, $onlyAudio)
    {
        return array_filter($profiles, function ($elem) use ($onlyAudio) {
            return !$onlyAudio || $elem['audio'];
        });
    }

    /**
     * Count Multimedia Objects.
     *
     * @param Series $series
     *
     * @return int
     */
    public function countMultimediaObjects($series)
    {
        return $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($series);
    }

    /**
     * Get Broadcast description.
     *
     * @param string $broadcastType
     * @param string $template
     * @param bool   $islive
     *
     * @return string
     */
    public function getBroadcastDescription($broadcastType, $template, $islive = false)
    {
        $description = '';

        $changeWord = 'multimedia object';
        if ($islive) {
            $changeWord = 'live event';
        }
        if ((EmbeddedBroadcast::TYPE_PUBLIC === $broadcastType) && $template) {
            $description = $this->translator->trans('Any Internet user can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_PUBLIC === $broadcastType) {
            $description = $this->translator->trans("Any Internet user can play this $changeWord");
        } elseif ((EmbeddedBroadcast::TYPE_PASSWORD === $broadcastType) && $template) {
            $description = $this->translator->trans('Only users with the defined password can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_PASSWORD === $broadcastType) {
            $description = $this->translator->trans("Only users with the defined password can play this $changeWord");
        } elseif ((EmbeddedBroadcast::TYPE_LOGIN === $broadcastType) && $template) {
            $description = $this->translator->trans('Only logged in users in the system can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_LOGIN === $broadcastType) {
            $description = $this->translator->trans("Only logged in users in the system can play this $changeWord");
        } elseif ((EmbeddedBroadcast::TYPE_GROUPS === $broadcastType) && $template) {
            $description = $this->translator->trans('Only users in the selected Groups can play the new multimedia objects created from this video template');
        } elseif (EmbeddedBroadcast::TYPE_GROUPS === $broadcastType) {
            $description = $this->translator->trans("Only users in the selected Groups can play this $changeWord");
        }

        return $description;
    }

    //TODO: Pass to a SERVICE
    private function countMmobjsByStatus($series)
    {
        if (isset($this->countMmobjsByStatus[$series->getId()])) {
            return $this->countMmobjsByStatus[$series->getId()];
        }
        $mmobjsPublished = 0;
        $mmobjsHidden = 0;
        $mmobjsBlocked = 0;

        $seriesColl = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
        $aggrPipe = array(
            array('$match' => array('series' => new \MongoId($series->getId()))),
            array('$group' => array('_id' => '$status',
                                    'count' => array('$sum' => 1), )),
        );
        $mmobjCounts = $seriesColl->aggregate($aggrPipe)->toArray();

        foreach ($mmobjCounts as $mmobjCount) {
            switch ($mmobjCount['_id']) {
                case MultimediaObject::STATUS_PUBLISHED:
                    $mmobjsPublished = $mmobjCount['count'];
                    break;
                case MultimediaObject::STATUS_HIDE:
                    $mmobjsHidden = $mmobjCount['count'];
                    break;
                case MultimediaObject::STATUS_BLOQ:
                    $mmobjsBlocked = $mmobjCount['count'];
                    break;
            }
        }

        $result = array($mmobjsPublished, $mmobjsHidden, $mmobjsBlocked);

        return $this->countMmobjsByStatus[$series->getId()] = $result;
    }

    //TODO: Pass to a SERVICE
    private function countMmobjsWithTag($series, $tagCod)
    {
        if (isset($this->countMmobjsWithTag[$series->getId()][$tagCod])) {
            return $this->countMmobjsWithTag[$series->getId()][$tagCod];
        }
        $repoSeries = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $qb = $repoSeries->createStandardQueryBuilder()->field('series')->equals(new \MongoId($series->getId()))->field('tags.cod')->equals('PUDENEW');
        $count = $qb->count()->getQuery()->execute();

        $this->countMmobjsWithTag[$series->getId()][$tagCod] = $count;

        return $count;
    }

    /**
     * Returns a boolean with whether the mmobj will be played on a playlist.
     *
     * @param MultimediaObject $mmobj
     *
     * @return bool
     */
    public function isPlayableOnPlaylist($mmobj)
    {
        return $this->mmobjService->isPlayableOnPlaylist($mmobj);
    }

    /**
     * Returns a boolean is user is owner.
     *
     * @param User             $user
     * @param MultimediaObject $mmobj
     *
     * @return bool
     */
    public function isUserOwner($user, $mmobj)
    {
        return $this->mmobjService->isUserOwner($user, $mmobj);
    }

    /**
     * Returns a boolean is request a naked backoffice.
     *
     *
     * @return bool
     */
    public function isNaked(\Twig_Environment $env)
    {
        if (isset($env->getGlobals()['app'])) {
            return $env->getGlobals()['app']->getRequest()->attributes->get('nakedbackoffice', false);
        }

        return false;
    }

    /**
     * Returns the embbedded Broadcast
     * __toString() function translated.
     *
     * @param EmbeddedBroadcast $embeddedBroadcast
     *
     * @return string
     */
    public function getI18nEmbeddedBroadcast(EmbeddedBroadcast $embeddedBroadcast, $locale = 'en')
    {
        return $this->specialTranslationService->getI18nEmbeddedBroadcast($embeddedBroadcast, $locale);
    }

    /**
     * @param MultimediaObject $multimediaObject
     *
     * @return mixed
     */
    public function getDateFromMongoId(MultimediaObject $multimediaObject)
    {
        $id = new \MongoId($multimediaObject->getId());

        return $id->getTimestamp();
    }

    /**
     * Returns session that are reproducing now or the next session to reproduce it.
     *
     * @param $multimediaObject
     *
     * @return bool|mixed
     */
    public function getNextEventSession($multimediaObject)
    {
        if ($multimediaObject) {
            $now = new \DateTime();
            $now = $now->getTimestamp();
            $aSessions = array();
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
            } else {
                return false;
            }
        }
    }

    /**
     * Get Default Poster.
     *
     * @returns string
     */
    public function getDefaultPoster()
    {
        return $this->eventService->getDefaultPoster();
    }

    /**
     * @param $multimediaObject
     * @param bool $display
     *
     * @return array
     */
    public function getSortRoles($multimediaObject, $display = true)
    {
        static $rolesCached = array();

        if (isset($rolesCached[$display])) {
            $roles = $rolesCached[$display];
        } else {
            $roles = $this->dm->getRepository('PumukitSchemaBundle:Role')->findBy(array('display' => $display), array('rank' => 1));
            $rolesCached[$display] = $roles;
        }

        $aRoles = array();
        foreach ($roles as $role) {
            $embeddedRole = $multimediaObject->getEmbeddedRole($role);
            if ($embeddedRole && 0 != count($embeddedRole->getPeople())) {
                $aRoles[] = $embeddedRole;
            }
        }

        return $aRoles;
    }
}
