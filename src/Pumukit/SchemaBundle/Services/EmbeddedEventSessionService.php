<?php

namespace Pumukit\SchemaBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\EmbeddedEvent;
use Doctrine\ODM\MongoDB\DocumentManager;

class EmbeddedEventSessionService
{
    private $dm;
    private $repo;
    private $collection;

    const DEFAULT_POSTER = '/bundles/pumukitwebtv/images/live_screen.jpg';
    const DEFAULT_COLOR = 'white';

    private $validColors = array(
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
    );

    /**
     * Constructor.
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->collection = $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject');
    }

    /**
     * Find current events.
     */
    public function findEventsNow()
    {
        $now = new \MongoDate();

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array(
                'sessions.start' => array('$exists' => true),
                'sessionEnds' => array('$gte' => $now),
                'sessions.start' => array('$lte' => $now),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'sessions.start' => -1,
            ),
        );

        $this->endPipeline($pipeline);

        $pipeline[] = array('$limit' => 10);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    /**
     * Find today events.
     */
    public function findEventsToday()
    {
        $todayStarts = strtotime(date('Y-m-d H:i:s', mktime(00, 00, 00, date('m'), date('d'), date('Y'))));
        $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array('$and' => array(
                array('sessions.start' => array('$gte' => new \MongoDate($todayStarts))),
                array('sessions.start' => array('$lte' => new \MongoDate($todayEnds))),
            )),
        );

        $this->endPipeline($pipeline);

        $pipeline[] = array('$limit' => 20);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    /**
     * Find next events.
     */
    public function findNextEvents()
    {
        $todayEnds = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));

        $pipeline = $this->initPipeline();

        $pipeline[] = array(
            '$match' => array(
                'sessions.start' => array('$exists' => true),
                'sessions.start' => array('$gte' => new \MongoDate($todayEnds)),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'sessions.start' => 1,
            ),
        );

        $this->endPipeline($pipeline);

        return $this->collection->aggregate($pipeline)->toArray();
    }

    /**
     * Get event poster.
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
     * Get poster text color.
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
     * Get event starts date
     *
     * @param EmbeddedEvent
     *
     * @returns Date
     */
    public function getEventStartsDate($event)
    {
        $date = $event->getDate();
        $sessions = $event->getEmbeddedEventSession();
        foreach ($sessions as $session) {
            if ($session->getStart() < $date) {
                $date = $session->getStart();
            }
        }

        return $date;
    }

    /**
     * Get event ends date
     *
     * @param EmbeddedEvent
     *
     * @returns Date
     */
    public function getEventEndsDate($event)
    {
        $date = $event->getDate();
        $sessions = $event->getEmbeddedEventSession();
        foreach ($sessions as $session) {
            if ($session->getEnds() > $date) {
                $date = $session->getEnds();
            }
        }

        return $date;
    }

    private function initPipeline()
    {
        $pipeline = array();

        $pipeline[] = array(
            '$match' => array(
                'islive' => true,
                'embeddedEvent.display' => true,
                'embeddedEvent.embeddedEventSession' => array('$exists' => true),
            ),
        );

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$_id',
                'event' => '$embeddedEvent',
                'sessions' => '$embeddedEvent.embeddedEventSession',
                'pics' => '$pics',
            ),
        );

        $pipeline[] = array('$unwind' => '$sessions');

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$multimediaObjectId',
                'event' => '$event',
                'sessions' => '$sessions',
                'pics' => '$pics',
                'sessionEnds' => array(
                    '$add' => array(
                        '$sessions.start',
                        array(
                            '$multiply' => array(
                                '$sessions.duration',
                                1000,
                            ),
                        ),
                    ),
                ),
            ),
        );

        return $pipeline;
    }

    private function endPipeline(&$pipeline)
    {
        $pipeline[] = array(
            '$group' => array(
                '_id' => '$multimediaObjectId',
                'data' => array(
                    '$first' => array(
                        'event' => '$event',
                        'session' => '$sessions',
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ),
                ),
            ),
        );

        $pipeline[] = array(
            '$sort' => array(
                'data.session.start' => 1,
            ),
        );
    }

    private function getMultimediaObjectPics($eventId)
    {
        $pipeline = array();

        $pipeline[] = array(
            '$match' => array(
                'embeddedEvent._id' => new \MongoId($eventId),
            ),
        );

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$_id',
                'pics' => '$pics',
            ),
        );

        $pipeline[] = array(
            '$group' => array(
                '_id' => '$multimediaObjectId',
                'data' => array(
                    '$first' => array(
                        'multimediaObjectId' => '$multimediaObjectId',
                        'pics' => '$pics',
                    ),
                ),
            ),
        );

        $data = $this->collection->aggregate($pipeline)->toArray();

        if (isset($data[0]['data']['pics'])) {
            return $data[0]['data']['pics'];
        }

        return array();
    }

    private function getPoster($pics)
    {
        foreach ($pics as $pic) {
            if (isset($pic['tags'])) {
                if (in_array('poster', $pic['tags']) && isset($pic['url'])) {
                    return $pic['url'];
                }
            }
        }

        return self::DEFAULT_POSTER;
    }

    private function getMultimediaObjectProperties($eventId)
    {
        $pipeline = array();

        $pipeline[] = array(
            '$match' => array(
                'embeddedEvent._id' => new \MongoId($eventId),
            ),
        );

        $pipeline[] = array(
            '$project' => array(
                'multimediaObjectId' => '$_id',
                'properties' => '$properties',
            ),
        );

        $pipeline[] = array(
            '$group' => array(
                '_id' => '$multimediaObjectId',
                'data' => array(
                    '$first' => array(
                        'multimediaObjectId' => '$multimediaObjectId',
                        'properties' => '$properties',
                    ),
                ),
            ),
        );

        $data = $this->collection->aggregate($pipeline)->toArray();

        if (isset($data[0]['data']['properties'])) {
            return $data[0]['data']['properties'];
        }

        return array();
    }
}
