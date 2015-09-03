<?php

namespace Pumukit\PodcastBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;

/**
 * @Route("/podcast")
 */
class DefaultController extends Controller
{
    const ITUNES_DTD_URL = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    const ITUNESU_FEED_URL = 'https://www.itunesu.com/feed';
    const ATOM_URL = 'http://www.w3.org/2005/Atom';

    /**
     * @Route("/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_video")
     */
    public function videoAction(Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(false);
        try {
            $values = $this->getValues($request, 'video', null);
            $xml = $this->getXMLElement($multimediaObjects, $values, 'video');
        } catch (\Exception $e) {
            $xml = $this->getXMLErrorElement($e);
            return new Response($xml->asXML(), 400, array('Content-Type' => 'text/xml'));
        }
        return new Response($xml->asXML(), 200, array('Content-Type' => 'text/xml'));
    }

    /**
     * @Route("/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_audio")
     */
    public function audioAction(Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(true);
        try {
            $values = $this->getValues($request, 'audio', null);
            $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');
        } catch (\Exception $e) {
            $xml = $this->getXMLErrorElement($e);
            return new Response($xml->asXML(), 400, array('Content-Type' => 'text/xml'));
        }
        return new Response($xml->asXML(), 200, array('Content-Type' => 'text/xml'));
    }

    /**
     * @Route("/series/{id}/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_video")
     */
    public function seriesVideoAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(false, $series);
        try {
            $values = $this->getValues($request, 'video', $series);
            $xml = $this->getXMLElement($multimediaObjects, $values, 'video');
        } catch (\Exception $e) {
            $xml = $this->getXMLErrorElement($e);
            return new Response($xml->asXML(), 400, array('Content-Type' => 'text/xml'));
        }
        return new Response($xml->asXML(), 200, array('Content-Type' => 'text/xml'));
    }

    /**
     * @Route("/series/{id}/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_audio")
     */
    public function seriesAudioAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(true, $series);
        try {
            $values = $this->getValues($request, 'audio', $series);
            $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');
        } catch (\Exception $e) {
            $xml = $this->getXMLErrorElement($e);
            return new Response($xml->asXML(), 400, array('Content-Type' => 'text/xml'));
        }
        return new Response($xml->asXML(), 200, array('Content-Type' => 'text/xml'));
    }

    /**
     * @Route("/series/{id}/collection.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_collection")
     */
    public function seriesCollectionAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsBySeries($series);
        try {
            $values = $this->getValues($request, 'video', $series);
            $xml = $this->getXMLElement($multimediaObjects, $values, 'all');
        } catch (\Exception $e) {
            $xml = $this->getXMLErrorElement($e);
            return new Response($xml->asXML(), 400, array('Content-Type' => 'text/xml'));
        }
        return new Response($xml->asXML(), 200, array('Content-Type' => 'text/xml'));
    }

    private function createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio=false)
    {
	    $mmObjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $qb = $mmObjRepo->createStandardQueryBuilder();
        $qb->field('tracks')->elemMatch(
            $qb->expr()->field('only_audio')->equals($isOnlyAudio)
                ->field('tags')->equals('podcast')
        );
        return $qb;
    }

    private function getPodcastMultimediaObjectsByAudio($isOnlyAudio=false)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);
        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsByAudioAndSeries($isOnlyAudio=false, Series $series)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);
        $qb->field('series')->references($series);
        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsBySeries(Series $series)
    {
        $mmObjRepo = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $qb = $mmObjRepo->createStandardQueryBuilder()
          ->field('series')->references($series)
          ->field('tracks.tags')->equals('podcast');
        return $qb->getQuery()->execute();
    }

    private function getValues(Request $request, $audioVideoType='video', $series=null)
    {
        $container = $this->container;
        $pumukit2Info = $container->getParameter('pumukit2.info');
        $assetsHelper = $container->get('templating.helper.assets');

        $values = array();
        $values['base_url'] = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $values['requestURI'] = $request->get('reguestURI');
        $values['image_url'] = $values['base_url'] . $assetsHelper->getUrl('/bundles/pumukitpodcast/images/gc_'.$audioVideoType.'.jpg');
        $values['language'] = $request->getLocale();
        $values['itunes_author'] = $container->getParameter('pumukit_podcast.itunes_author');
        $values['email'] = $pumukit2Info['email'];
        $values['itunes_explicit'] = $container->getParameter('pumukit_podcast.itunes_explicit') ? 'yes' : 'no';

        if ($series) {
            $values['channel_title'] = $series->getTitle();
            $values['channel_description'] = $series->getDescription();
            $values['copyright'] = $series->getCopyright() ? $series->getCopyright() : 'PuMuKIT2 2015';
            $values['itunes_category'] = $series->getProperty('itunescategory') ? $series->getProperty('itunescategory') : $container->getParameter('pumukit_podcast.itunes_category');
            $values['itunes_summary'] = $series->getDescription();
            $values['itunes_subtitle'] = $series->getSubtitle() ? $series->getSubtitle() :
              ($container->hasParameter('pumukit_podcast.itunes_subtitle') ? $container->getParameter('pumukit_podcast.itunes_subtitle') : $values['channel_description']);
        } else {
            $values['channel_title'] = $container->hasParameter('pumukit_podcast.channel_title') ?
              $container->getParameter('pumukit_podcast.channel_title') :
              $pumukit2Info['title'];
            $values['channel_description'] = $container->hasParameter('pumukit_podcast.channel_description') ?
              $container->getParameter('pumukit_podcast.channel_description') :
              $pumukit2Info['description'];
            $values['copyright'] = $container->hasParameter('pumukit_podcast.channel_copyright') ?
              $container->getParameter('pumukit_podcast.channel_copyright') :
              (isset($pumukit2Info['copyright']) ? $pumukit2Info['copyright'] : 'PuMuKIT2 2015');
            $values['itunes_category'] = $container->getParameter('pumukit_podcast.itunes_category');
            $values['itunes_summary'] = $container->hasParameter('pumukit_podcast.itunes_summary') ?
              $container->getParameter('pumukit_podcast.itunes_summary') :
              $values['channel_description'];
            $values['itunes_subtitle'] = $container->hasParameter('pumukit_podcast.itunes_subtitle') ?
              $container->getParameter('pumukit_podcast.itunes_subtitle') : $values['channel_description'];
        }

        return $values;
    }

    private function getXMLElement($multimediaObjects, $values, $trackType='video')
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                                     .'<rss xmlns:itunes="'.self::ITUNES_DTD_URL
                                     .'" xmlns:itunesu="'.self::ITUNESU_FEED_URL
                                     .'" xmlns:atom="'.self::ATOM_URL
                                     .'" xml:lang="en" version="2.0"></rss>'
                                     );
        $atomLink = $xml->addChild('atom:link', null, self::ATOM_URL);
        $atomLink->addAttribute('href', $values['requestURI']);
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');
        $channel = $xml->addChild('channel');
        $channel->addChild('title', $values['channel_title']);
        $channel->addChild('link', $values['base_url']);
        $channel->addChild('description', $values['channel_description']);
        $channel->addChild('generator', 'PuMuKiT');
        $channel->addChild('lastBuildDate', (new \DateTime('now'))->format('r'));
        $channel->addChild('language', $values['language']);
        $channel->addChild('copyright', $values['copyright']);

        $itunesImage = $channel->addChild('itunes:image', null, self::ITUNES_DTD_URL);
        $itunesImage->addAttribute('href', $values['image_url']);

        $image = $channel->addChild('image');
        $image->addChild('url', $values['image_url']);
        $image->addChild('title', $values['channel_title']);
        $image->addChild('link', $values['base_url']);

        $itunesCategory = $channel->addChild('itunes:category', null, self::ITUNES_DTD_URL);
        $itunesCategory->addAttribute('text', $values['itunes_category']);

        $channel->addChild('itunes:summary', $values['itunes_summary'], self::ITUNES_DTD_URL);
        $channel->addChild('itunes:subtitle', $values['itunes_subtitle'], self::ITUNES_DTD_URL);
        $channel->addChild('itunes:author', $values['itunes_author'], self::ITUNES_DTD_URL);

        $itunesOwner = $channel->addChild('itunes:owner', null, self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:name', $values['itunes_author'], self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:email', $values['email'], self::ITUNES_DTD_URL);

        $channel->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);

        $channel = $this->completeTracksInfo($channel, $multimediaObjects, $values, $trackType);

        return $xml;
    }

    private function completeTracksInfo($channel, $multimediaObjects, $values, $trackType='video')
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $tagRepo = $dm->getRepository('PumukitSchemaBundle:Tag');
        $itunesTag = $tagRepo->findOneByCod('ITUNES');
        foreach ($multimediaObjects as $multimediaObject) {
            $track = $this->getPodcastTrack($multimediaObject, $trackType);

            $item = $channel->addChild('item');

            $title = (strlen($multimediaObject->getTitle()) === 0) ?
              $multimediaObject->getSeries()->getTitle() :
              $multimediaObject->getTitle();
            $item->addChild('title', $title);
            $item->addChild('itunes:subtitle', $multimediaObject->getSubtitle(), self::ITUNES_DTD_URL);
            $item->addChild('itunes:summary', $multimediaObject->getDescription(), self::ITUNES_DTD_URL);
            $item->addChild('description', $multimediaObject->getDescription());

            if ($itunesTag !== null) {
                foreach ($multimediaObject->getTags() as $tag) {
                    if ($tag->isDescendantOf($itunesTag)){
                        $embeddedTag = $tag;
                        break;
                    }
                }
                $itunesUCategory = $item->addChild('itunesu:category', null, self::ITUNESU_FEED_URL);
                // TODO review adding itunesUFeedUrl
                $itunesUCategory->addAttribute('itunesu:code', $embeddedTag->getCod(), self::ITUNESU_FEED_URL);
            }

            $item->addChild('link', $values['base_url'] . $track->getUrl());

            $enclosure = $item->addChild('enclosure');
            $enclosure->addAttribute('url', $values['base_url'] . $track->getUrl());
            $enclosure->addAttribute('length', $track->getSize());
            $enclosure->addAttribute('type', $track->getMimeType());

            $item->addChild('guid', $values['base_url'] . $track->getUrl());
            $item->addChild('itunes:duration', $multimediaObject->getDurationString(), self::ITUNES_DTD_URL);
            $item->addChild('author', $multimediaObject->getCopyright());
            $item->addChild('itunes:author', $multimediaObject->getCopyright(), self::ITUNES_DTD_URL);
            $item->addChild('itunes:keywords', $multimediaObject->getKeyword(), self::ITUNES_DTD_URL);
            $item->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);
            $item->addChild('pubDate', $multimediaObject->getRecordDate()->format('r'));
        }

        return $channel;
    }

    private function getXMLErrorElement(\Exception $e)
    {
        $xml = new \SimpleXMLElement('<error>'.$e->getMessage().'</error>');
        return $xml;
    }

    private function getPodcastTrack(MultimediaObject $multimediaObject, $trackType='video')
    {
        if ('all' === $trackType) {
            $track = $this->getVideoTrack($multimediaObject);
            if (null === $track) {
                $track = $this->getAudioTrack($multimediaObject);
            }
        } elseif ('video' === $trackType) {
            $track = $this->getVideoTrack($multimediaObject);
        } else {
            $track = $this->getAudioTrack($multimediaObject);
        }

        return $track;
    }

    private function getVideoTrack(MultimediaObject $multimediaObject)
    {
        $video_all_tags = array('display', 'podcast');
        $video_not_all_tags = array('audio');
        return $multimediaObject->getFilteredTrackWithTags(
                                                           array(),
                                                           $video_all_tags,
                                                           array(),
                                                           $video_not_all_tags,
                                                           false);
    }

    private function getAudioTrack(MultimediaObject $multimediaObject)
    {
        $audio_all_tags = array('display', 'podcast', 'audio');
        $audio_not_all_tags = array();
        return $multimediaObject->getFilteredTrackWithTags(
                                                           array(),
                                                           $audio_all_tags,
                                                           array(),
                                                           $audio_not_all_tags,
                                                           false);
    }
}
