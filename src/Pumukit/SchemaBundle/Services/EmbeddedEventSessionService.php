<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class EmbeddedEventSessionService
{
    const DEFAULT_COLOR = '#ffffff';
    private $dm;
    private $collection;
    private $defaultPoster;
    private $defaultThumbnail;
    private $validColors = [
        'aliceblue',
        'antiquewhite',
        'aqua',
        'aquamarine',
        'azure',
        'beige',
        'bisque',
        'black',
        'blanchedalmond',
        'blue',
        'blueviolet',
        'brown',
        'burlywood',
        'cadetblue',
        'chartreuse',
        'chocolate',
        'coral',
        'cornflowerblue',
        'cornsilk',
        'crimson',
        'cyan',
        'darkblue',
        'darkcyan',
        'darkgoldenrod',
        'darkgray',
        'darkgreen',
        'darkkhaki',
        'darkmagenta',
        'darkolivegreen',
        'darkorange',
        'darkorchid',
        'darkred',
        'darksalmon',
        'darkseagreen',
        'darkslateblue',
        'darkslategray',
        'darkturquoise',
        'darkviolet',
        'deeppink',
        'deepskyblue',
        'dimgray',
        'dodgerblue',
        'firebrick',
        'floralwhite',
        'forestgreen',
        'fuchsia',
        'gainsboro',
        'ghostwhite',
        'gold',
        'goldenrod',
        'gray',
        'green',
        'greenyellow',
        'honeydew',
        'hotpink',
        'indianred',
        'indigo',
        'ivory',
        'khaki',
        'lavender',
        'lavenderblush',
        'lawngreen',
        'lemonchiffon',
        'lightblue',
        'lightcoral',
        'lightcyan',
        'lightgoldenrodyellow',
        'lightgreen',
        'lightgrey',
        'lightpink',
        'lightsalmon',
        'lightseagreen',
        'lightskyblue',
        'lightslategray',
        'lightsteelblue',
        'lightyellow',
        'lime',
        'limegreen',
        'linen',
        'magenta',
        'maroon',
        'mediumaquamarine',
        'mediumblue',
        'mediumorchid',
        'mediumpurple',
        'mediumseagreen',
        'mediumslateblue',
        'mediumspringgreen',
        'mediumturquoise',
        'mediumvioletred',
        'midnightblue',
        'mintcream',
        'mistyrose',
        'moccasin',
        'navajowhite',
        'navy',
        'oldlace',
        'olive',
        'olivedrab',
        'orange',
        'orangered',
        'orchid',
        'palegoldenrod',
        'palegreen',
        'paleturquoise',
        'palevioletred',
        'papayawhip',
        'peachpuff',
        'peru',
        'pink',
        'plum',
        'powderblue',
        'purple',
        'red',
        'rosybrown',
        'royalblue',
        'saddlebrown',
        'salmon',
        'sandybrown',
        'seagreen',
        'seashell',
        'sienna',
        'silver',
        'skyblue',
        'slateblue',
        'slategray',
        'snow',
        'springgreen',
        'steelblue',
        'tan',
        'teal',
        'thistle',
        'tomato',
        'turquoise',
        'violet',
        'wheat',
        'white',
        'whitesmoke',
        'yellow',
        'yellowgreen',
    ];

    /**
     * EmbeddedEventSessionService constructor.
     *
     * @param DocumentManager $documentManager
     * @param string          $defaultPoster
     * @param string          $defaultThumbnail
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function __construct(DocumentManager $documentManager, $defaultPoster, $defaultThumbnail)
    {
        $this->dm = $documentManager;
        $this->collection = $this->dm->getDocumentCollection(MultimediaObject::class);
        $this->defaultPoster = $defaultPoster;
        $this->defaultThumbnail = $defaultThumbnail;
    }

    /**
     * Get default poster.
     *
     * @return string
     */
    public function getDefaultPoster()
    {
        return $this->defaultPoster;
    }

    /**
     * Get default thumbnail.
     *
     * @return string
     */
    public function getDefaultThumbnail()
    {
        return $this->defaultThumbnail;
    }

    /**
     * Find current events.
     */
    public function findEventsNow()
    {
        $now = new \MongoDate();
        $pipeline = $this->initPipeline();
        $pipeline[] = [
            '$match' => [
                'sessionEnds' => ['$gte' => $now],
                'sessions.start' => ['$lte' => $now],
            ],
        ];
        $pipeline[] = [
            '$sort' => [
                'sessions.start' => -1,
            ],
        ];
        $this->endPipeline($pipeline);
        $pipeline[] = ['$limit' => 10];

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * Find today events.
     */
    public function findEventsToday()
    {
        $todayStarts = mktime(00, 00, 00, date('m'), date('d'), date('Y'));
        $todayEnds = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $pipeline = $this->initPipeline();
        $pipeline[] = [
            '$match' => ['$and' => [
                ['sessions.start' => ['$gte' => new \MongoDate($todayStarts)]],
                ['sessions.start' => ['$lte' => new \MongoDate($todayEnds)]],
            ]],
        ];
        $this->endPipeline($pipeline);
        $pipeline[] = ['$limit' => 20];

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * Find next events.
     */
    public function findNextEvents()
    {
        $todayEnds = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $pipeline = $this->initPipeline();
        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$gte' => new \MongoDate($todayEnds)],
            ],
        ];
        $pipeline[] = [
            '$sort' => [
                'sessions.start' => 1,
            ],
        ];
        $this->endPipeline($pipeline);

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function findWidgetEvents($limit = 0)
    {
        $pipeline = $this->initPipeline();
        $date = new \DateTime('now');
        $now = new \MongoDate($date->format('U'));
        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$exists' => true],
                'sessions.ends' => ['$gte' => $now],
            ],
        ];
        $pipeline[] = [
            '$sort' => [
                'sessions.start' => 1,
            ],
        ];
        $this->endPipeline($pipeline);

        if ($limit > 0) {
            $pipeline[] = ['$limit' => $limit];
        }

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * Get current sessions with or without criteria.
     *
     * @param array $criteria
     * @param int   $limit
     * @param bool  $all      true to not filter by display
     *
     * @return array
     */
    public function findCurrentSessions($criteria = [], $limit = 0, $all = false)
    {
        static $currentSessions = [];

        $encryptCriteria = md5(json_encode($criteria).(string) $limit.(string) $all);

        if (isset($currentSessions[$encryptCriteria])) {
            return $currentSessions[$encryptCriteria];
        }

        $pipeline = $this->initPipeline($all);

        if ($criteria && !empty($criteria)) {
            $pipeline[] = [
                '$match' => $criteria,
            ];
        }

        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$lt' => new \MongoDate()],
                'sessionEnds' => ['$gt' => new \MongoDate()],
            ],
        ];

        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'pics' => '$pics',
                'sessions' => '$sessions',
                'session' => '$sessions',
                'sessionEnds' => '$sessionEnds',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                        'session' => '$session',
                        'multimediaObjectId' => '$multimediaObjectId',
                        'sessionEnds' => '$sessionEnds',
                        'pics' => '$pics',
                    ],
                ],
            ],
        ];

        if ($limit > 0) {
            $pipeline[] = ['$limit' => $limit];
        }

        $currentSessions[$encryptCriteria] = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();

        return $currentSessions[$encryptCriteria];
    }

    /**
     * Get next sessions with or without criteria.
     *
     * @param array $criteria
     * @param int   $limit
     * @param bool  $all      true to not filter by display
     *
     * @return array
     */
    public function findNextSessions($criteria = [], $limit = 0, $all = false)
    {
        static $findNextSessions;

        $encryptCriteria = md5(json_encode($criteria).(string) $limit.(string) $all);

        if (isset($findNextSessions[$encryptCriteria])) {
            return $findNextSessions[$encryptCriteria];
        }

        $pipeline = $this->initPipeline($all);

        if ($criteria && !empty($criteria)) {
            $pipeline[] = [
                '$match' => $criteria,
            ];
        }

        $pipeline[] = [
            '$match' => [
                '$and' => [
                    ['sessions.start' => ['$exists' => true]],
                    ['sessions.start' => ['$gt' => new \MongoDate()]],
                ],
            ],
        ];

        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'pics' => '$pics',
                'session' => '$sessions',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                        'session' => '$session',
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ],
                ],
            ],
        ];

        if ($limit > 0) {
            $pipeline[] = ['$limit' => $limit];
        }

        $result = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();

        foreach ($result as $key => $element) {
            $orderSession = [];
            foreach ($element['data'] as $eventData) {
                $orderSession[$eventData['session']['start']->sec] = $eventData;
            }
            ksort($orderSession);
            $result[$key]['data'] = array_values($orderSession);
        }

        $findNextSessions[$encryptCriteria] = $result;

        return $findNextSessions[$encryptCriteria];
    }

    /**
     * Get sessions to show on menu of WebTV.
     *
     * @param array $criteria
     * @param int   $limit
     *
     * @return array
     */
    public function findEventsMenu(array $criteria = [], $limit = 0)
    {
        $todayStarts = mktime(00, 00, 00, date('m'), date('d'), date('Y'));

        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'type' => MultimediaObject::TYPE_LIVE,
                'embeddedEvent.display' => true,
                'embeddedEvent.embeddedEventSession' => ['$exists' => true],
            ],
        ];

        if ($criteria && !empty($criteria)) {
            $pipeline[] = [
                '$match' => $criteria,
            ];
        }

        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
            ],
        ];

        $pipeline[] = ['$unwind' => '$sessions'];

        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$event',
                'sessions' => '$sessions',
                'seriesTitle' => '$seriesTitle',
            ],
        ];

        $time = new \MongoDate(time());
        $pipeline[] = [
            '$match' => [
                '$or' => [
                    [
                        'sessions.start' => ['$gte' => new \MongoDate($todayStarts)],
                    ],
                    [
                        'sessions.start' => ['$lt' => $time],
                        'sessions.ends' => ['$gt' => $time],
                    ],
                ],
            ],
        ];

        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'seriesTitle' => '$seriesTitle',
                'session' => '$sessions',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                        'session' => '$session',
                    ],
                ],
            ],
        ];

        if ($limit > 0) {
            $pipeline[] = ['$limit' => $limit];
        }

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * Get event poster.
     *
     * @deprecated: Use getEventPicPoster
     *
     * @param EmbeddedEvent $event
     *
     * @return string
     */
    public function getEventPoster(EmbeddedEvent $event)
    {
        $pics = $this->getMultimediaObjectPics($event->getId());

        return $this->getPoster($pics);
    }

    /**
     * Get event poster.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getEventPicPoster(MultimediaObject $multimediaObject)
    {
        return $this->getPicPoster($multimediaObject);
    }

    /**
     * Get event poster by event id.
     *
     * @param string $eventId
     *
     * @return string
     */
    public function getEventPosterByEventId($eventId)
    {
        $pics = $this->getMultimediaObjectPics($eventId);

        return $this->getPoster($pics);
    }

    /**
     * Get event thumbnail.
     *
     * @param EmbeddedEvent $event
     *
     * @return string
     */
    public function getEventThumbnail(EmbeddedEvent $event)
    {
        $pics = $this->getMultimediaObjectPics($event->getId());

        return $this->getFirstThumbnail($pics);
    }

    /**
     * Get event thumbnail by event id.
     *
     * @param string $eventId
     *
     * @return string
     */
    public function getEventThumbnailByEventId($eventId)
    {
        $pics = $this->getMultimediaObjectPics($eventId);

        return $this->getFirstThumbnail($pics);
    }

    /**
     * Get first thumbnail.
     *
     * @param array $pics
     *
     * @return string
     */
    public function getFirstThumbnail($pics)
    {
        foreach ($pics as $pic) {
            if ($pic['hide']) {
                continue;
            }
            if (isset($pic['tags']) && in_array('poster', $pic['tags'])) {
                continue;
            }
            if (isset($pic['url'])) {
                return $pic['url'];
            }
        }

        return $this->defaultThumbnail;
    }

    /**
     * Get poster text color.
     *
     * @deprecated NOTE: Use multimediaObject.getProperty('postertextcolor') to get text color and getDefaultPosterTextColor
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    public function getPicPosterTextColor(MultimediaObject $multimediaObject)
    {
        $posterTextColor = $multimediaObject->getProperty('postertextcolor');
        if (!$posterTextColor) {
            return self::DEFAULT_COLOR;
        }

        return $posterTextColor;
    }

    /**
     * Get poster text color.
     *
     * @deprecated Use getPicPosterTextColor
     *
     * @param EmbeddedEvent $event
     *
     * @return string
     */
    public function getPosterTextColor(EmbeddedEvent $event)
    {
        $properties = $this->getMultimediaObjectProperties($event->getId());
        if (isset($properties['postertextcolor'])) {
            return $properties['postertextcolor'];
        }

        return self::DEFAULT_COLOR;
    }

    /**
     * Validate HTML Color.
     *
     * @param string $color
     *
     * @throws \Exception
     *
     * @return string
     */
    public function validateHtmlColor($color)
    {
        if (in_array(strtolower($color), $this->validColors) ||
        preg_match('/^#[a-f0-9]{3}$/i', $color) ||
        preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return $color;
        }
        if (preg_match('/^[a-f0-9]{6}$/i', $color) ||
        preg_match('/^[a-f0-9]{3}$/i', $color)) {
            return '#'.$color;
        }

        throw new \Exception('Invalid text color: must be a hexadecimal number or a color name.');
    }

    /**
     * Get current session date.
     *
     * @param EmbeddedEvent $event
     * @param bool          $start
     *
     * @return \DateTime
     */
    public function getCurrentSessionDate(EmbeddedEvent $event, $start = true)
    {
        $now = new \DateTime('now');
        $date = new \DateTime('now');
        $sessions = $event->getEmbeddedEventSession();
        foreach ($sessions as $session) {
            if ($session->getStart() < $now && $session->getEnds() > $now) {
                $date = $start ? $session->getStart() : $session->getEnds();
            }
        }

        return $date;
    }

    /**
     * Get first session date.
     *
     * @param EmbeddedEvent $event
     * @param bool          $start
     *
     * @return \DateTime
     */
    public function getFirstSessionDate(EmbeddedEvent $event, $start = true)
    {
        $now = new \DateTime('now');
        foreach ($event->getEmbeddedEventSession() as $session) {
            if ($start && $session->getStart() && ($session->getStart() > $now)) {
                return $session->getStart();
            }
            if (!$start && $session->getEnds() && ($session->getEnds() > $now)) {
                return $session->getEnds();
            }
        }

        return $event->getDate();
    }

    /**
     * Get future session date.
     *
     * @param array $event
     * @param bool  $start
     *
     * @return string
     */
    public function getFutureSessionDate($event, $start = true)
    {
        $now = new \DateTime('now');
        if (isset($event['embeddedEventSession'])) {
            $date = $event['date'];
            usort($event['embeddedEventSession'], function ($a, $b) {
                return $a['start'] >= $b['start'];
            });
            foreach ($event['embeddedEventSession'] as $session) {
                if (!isset($session['start']) && !isset($session['ends'])) {
                    continue;
                }
                $dateStart = $session['start'];
                $dateEnds = $session['ends'];
                $dateStartSession = $dateStart->toDateTime();
                $dateEndsSession = $dateEnds->toDateTime();
                if (($dateStartSession < $now) || ($dateEndsSession < $now)) {
                    continue;
                }
                if ($start) {
                    return $dateStartSession;
                }

                return $dateEndsSession;
            }

            return $date->toDateTime();
        }

        return '';
    }

    /**
     * Get current session date.
     *
     * @param EmbeddedEvent $event
     * @param bool          $start
     *
     * @return bool
     */
    public function getShowEventSessionDate(EmbeddedEvent $event, $start = true)
    {
        $now = new \DateTime('now');
        $sessions = $event->getEmbeddedEventSession();
        foreach ($sessions as $session) {
            if ($session->getStart() < $now && $session->getEnds() > $now) {
                return $start ? $session->getStart() : $session->getEnds();
            }
            if ($session->getStart() > $now) {
                return $start ? $session->getStart() : $session->getEnds();
            }
            if ($session->getStart() < $now) {
                $date = $start ? $session->getStart() : $session->getEnds();
            }
        }
        if (isset($date)) {
            return $date;
        }

        return false;
    }

    /**
     * Find future events.
     *
     * @param null|string $multimediaObjectId
     * @param int         $limit
     *
     * @return array
     */
    public function findFutureEvents($multimediaObjectId = null, $limit = 0)
    {
        $pipeline = $this->getFutureEventsPipeline($multimediaObjectId);
        $result = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
        $orderSession = [];
        $now = new \DateTime('now');
        foreach ($result as $key => $element) {
            foreach ($element['data'] as $eventData) {
                usort($eventData['event']['embeddedEventSession'], function ($a, $b) {
                    return $a['start'] >= $b['start'];
                });
                foreach ($eventData['event']['embeddedEventSession'] as $embeddedSession) {
                    $startDate = $embeddedSession['start']->toDateTime();
                    if ($startDate > $now) {
                        $orderSession = $this->addElementWithSessionSec($orderSession, $element, $embeddedSession['start']->sec);

                        break;
                    }
                }
            }
        }
        ksort($orderSession);
        $output = [];
        foreach (array_values($orderSession) as $key => $session) {
            if (0 !== $limit && $key >= $limit) {
                break;
            }
            $output[$key] = $session;
        }

        return $output;
    }

    /**
     * Count future events.
     *
     * @param null|string $multimediaObjectId
     *
     * @return array
     */
    public function countFutureEvents($multimediaObjectId = null)
    {
        $pipeline = $this->getFutureEventsPipeline($multimediaObjectId);
        $result = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();

        return count($result);
    }

    /**
     * Find all events.
     *
     * @return array
     */
    public function findAllEvents()
    {
        $pipeline[] = [
            '$match' => [
                'type' => MultimediaObject::TYPE_LIVE,
                'embeddedEvent.display' => true,
                'embeddedEvent.embeddedEventSession' => ['$exists' => true],
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
            ],
        ];
        $pipeline[] = ['$unwind' => '$sessions'];
        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$exists' => true],
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'session' => '$sessions',
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                    ],
                ],
            ],
        ];

        return $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
    }

    /**
     * Find next live events.
     *
     * @param null|string $multimediaObjectId
     * @param int         $limit
     *
     * @return array
     */
    public function findNextLiveEvents($multimediaObjectId = null, $limit = 0)
    {
        $pipeline = $this->getNextLiveEventsPipeline($multimediaObjectId);
        $result = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
        $orderSession = [];
        $now = new \DateTime('now');
        foreach ($result as $key => $element) {
            foreach ($element['data'] as $eventData) {
                usort($eventData['event']['embeddedEventSession'], function ($a, $b) {
                    return $a['start'] >= $b['start'];
                });
                foreach ($eventData['event']['embeddedEventSession'] as $embeddedSession) {
                    $startDate = $embeddedSession['start']->toDateTime();
                    if ($startDate > $now) {
                        $orderSession = $this->addElementWithSessionSec($orderSession, $element, $embeddedSession['start']->sec);

                        break;
                    }
                }
            }
        }
        ksort($orderSession);
        $output = [];
        foreach (array_values($orderSession) as $key => $session) {
            if (0 !== $limit && $key >= $limit) {
                break;
            }
            $output[$key] = $session;
        }

        return $output;
    }

    /**
     * Is live broadcasting.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     *
     * @return bool
     */
    public function isLiveBroadcasting()
    {
        static $isLiveBroadcasting = null;

        if (null !== $isLiveBroadcasting) {
            return $isLiveBroadcasting;
        }

        $events = $this->findCurrentSessions();

        $isLiveBroadcasting = count($events) > 0;

        return $isLiveBroadcasting;
    }

    /**
     * Add element with session sec.
     *
     * @param array $orderSession
     * @param array $element
     * @param int   $indexSec
     *
     * @return array
     */
    protected function addElementWithSessionSec($orderSession, $element, $indexSec)
    {
        $index = 0;
        while (isset($orderSession[$indexSec + $index])) {
            ++$index;
        }
        $orderSession[$indexSec + $index] = $element;

        return $orderSession;
    }

    /**
     * Init pipeline.
     *
     * @param bool $all true to not filter by display
     *
     * @return array
     */
    private function initPipeline($all = false)
    {
        $pipeline = [];
        $pipeline[] = [
            '$match' => [
                'type' => MultimediaObject::TYPE_LIVE,
                'embeddedEvent.embeddedEventSession' => ['$exists' => true],
            ],
        ];
        if (!$all) {
            $pipeline[0]['$match']['embeddedEvent.display'] = true;
        }
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
                'pics' => '$pics',
                'embeddedBroadcast' => '$embeddedBroadcast',
            ],
        ];
        $pipeline[] = ['$unwind' => '$sessions'];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'pics' => '$pics',
                'embeddedBroadcast' => '$embeddedBroadcast',
                'sessionEnds' => [
                    '$add' => [
                        '$sessions.start',
                        [
                            '$multiply' => [
                                '$sessions.duration',
                                1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $pipeline;
    }

    /**
     * End pipeline.
     *
     * @param array $pipeline
     */
    private function endPipeline(&$pipeline)
    {
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$first' => [
                        'event' => '$event',
                        'session' => '$sessions',
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ],
                ],
            ],
        ];
        $pipeline[] = [
            '$sort' => [
                'data.session.start' => 1,
            ],
        ];
    }

    /**
     * Get future events pipeline.
     *
     * @param \MongoId|string $multimediaObjectId
     *
     * @return array
     */
    private function getFutureEventsPipeline($multimediaObjectId)
    {
        if ($multimediaObjectId) {
            $pipeline[] = [
                '$match' => [
                    '_id' => new \MongoId($multimediaObjectId),
                    'type' => MultimediaObject::TYPE_LIVE,
                    'embeddedEvent.embeddedEventSession' => ['$exists' => true],
                ],
            ];
        } else {
            $pipeline[] = [
                '$match' => [
                    'type' => MultimediaObject::TYPE_LIVE,
                    'embeddedEvent.display' => true,
                    'embeddedEvent.embeddedEventSession' => ['$exists' => true],
                ],
            ];
        }
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
            ],
        ];
        $pipeline[] = ['$unwind' => '$sessions'];
        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$gt' => new \MongoDate()],
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'session' => '$sessions',
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                    ],
                ],
            ],
        ];

        return $pipeline;
    }

    /**
     * Get multimedia object pics.
     *
     * @param \MongoId|string $eventId
     *
     * @return array
     */
    private function getMultimediaObjectPics($eventId)
    {
        $pipeline = [];
        $pipeline[] = [
            '$match' => [
                'embeddedEvent._id' => new \MongoId($eventId),
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'pics' => '$pics',
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$first' => [
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ],
                ],
            ],
        ];
        $data = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
        if (isset($data[0]['data']['pics'])) {
            return $data[0]['data']['pics'];
        }

        return [];
    }

    /**
     * Get poster.
     *
     * @deprecated: Use getPicPoster
     *
     * @param array $pics
     *
     * @return string
     */
    private function getPoster(array $pics)
    {
        foreach ($pics as $pic) {
            if (isset($pic['tags'])) {
                if (in_array('poster', $pic['tags']) && isset($pic['url'])) {
                    return $pic['url'];
                }
            }
        }

        return $this->defaultPoster;
    }

    /**
     * Get poster.
     *
     * @param MultimediaObject $multimediaObject
     *
     * @return string
     */
    private function getPicPoster(MultimediaObject $multimediaObject)
    {
        $poster = $multimediaObject->getPicWithTag('poster');
        if (!$poster) {
            return $this->defaultPoster;
        }

        return $poster->getUrl();
    }

    /**
     * Get multimedia object properties.
     *
     * @param \MongoId|string $eventId
     *
     * @return array
     */
    private function getMultimediaObjectProperties($eventId)
    {
        $pipeline = [];
        $pipeline[] = [
            '$match' => [
                'embeddedEvent._id' => new \MongoId($eventId),
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'properties' => '$properties',
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$first' => [
                        'multimediaObjectId' => '$multimediaObjectId',
                        'properties' => '$properties',
                    ],
                ],
            ],
        ];
        $data = $this->collection->aggregate($pipeline, ['cursor' => []])->toArray();
        if (isset($data[0]['data']['properties'])) {
            return $data[0]['data']['properties'];
        }

        return [];
    }

    /**
     * Get next live events pipeline.
     *
     * @param \MongoId|string $multimediaObjectId
     *
     * @return array
     */
    private function getNextLiveEventsPipeline($multimediaObjectId)
    {
        if ($multimediaObjectId) {
            $pipeline[] = [
                '$match' => [
                    '_id' => ['$nin' => [new \MongoId($multimediaObjectId)]],
                    'type' => MultimediaObject::TYPE_LIVE,
                    'embeddedEvent.display' => true,
                    'embeddedEvent.embeddedEventSession' => ['$exists' => true],
                ],
            ];
        } else {
            $pipeline[] = [
                '$match' => [
                    'type' => MultimediaObject::TYPE_LIVE,
                    'embeddedEvent.display' => true,
                    'embeddedEvent.embeddedEventSession' => ['$exists' => true],
                ],
            ];
        }
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
            ],
        ];
        $pipeline[] = ['$unwind' => '$sessions'];
        $now = new \MongoDate();
        $todayDate = new \DateTime('now');
        $today = new \MongoDate($todayDate->setTime(0, 0)->format('U'));
        $pipeline[] = [
            '$match' => [
                'sessions.start' => ['$gte' => $today],
                'sessions.ends' => ['$gte' => $now],
            ],
        ];
        $pipeline[] = [
            '$project' => [
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'session' => '$sessions',
            ],
        ];
        $pipeline[] = [
            '$group' => [
                '_id' => '$multimediaObjectId',
                'data' => [
                    '$addToSet' => [
                        'event' => '$event',
                    ],
                ],
            ],
        ];

        return $pipeline;
    }
}
