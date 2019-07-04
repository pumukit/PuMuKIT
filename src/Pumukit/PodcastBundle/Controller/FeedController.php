<?php

namespace Pumukit\PodcastBundle\Controller;

use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * @Route("/podcast")
 */
class FeedController extends Controller
{
    const ITUNES_DTD_URL = 'http://www.itunes.com/dtds/podcast-1.0.dtd';
    const ITUNESU_FEED_URL = 'http://www.itunesu.com/feed';
    const ATOM_URL = 'http://www.w3.org/2005/Atom';

    /**
     * @Route("/list.xml", defaults={"_format": "xml"}, name="pumukit_podcast_list")
     */
    public function listAction(Request $request)
    {
        $router = $this->get('router');
        $mmObjRepo = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository(MultimediaObject::class);

        $qb = $mmObjRepo->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $series = $qb->distinct('series')->getQuery()->execute();

        $xml = new \SimpleXMLElement('<list/>');
        foreach ($series as $s) {
            $url = $router->generate('pumukit_podcast_series_collection', ['id' => $s], UrlGeneratorInterface::ABSOLUTE_URL);
            $xml->addChild('podcast', $url);
        }

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_video")
     */
    public function videoAction(Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(false);
        $values = $this->getValues($request, 'video', null);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'video');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_audio")
     */
    public function audioAction(Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudio(true);
        $values = $this->getValues($request, 'audio', null);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/video.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_video")
     */
    public function seriesVideoAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(false, $series);
        $values = $this->getValues($request, 'video', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'video');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/audio.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_audio")
     */
    public function seriesAudioAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsByAudioAndSeries(true, $series);
        $values = $this->getValues($request, 'audio', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'audio');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/series/{id}/collection.xml", defaults={"_format": "xml"}, name="pumukit_podcast_series_collection")
     */
    public function seriesCollectionAction(Series $series, Request $request)
    {
        $multimediaObjects = $this->getPodcastMultimediaObjectsBySeries($series);
        $values = $this->getValues($request, 'video', $series);
        $xml = $this->getXMLElement($multimediaObjects, $values, 'all');

        return new Response($xml->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio = false)
    {
        $mmObjRepo = $this->get('doctrine_mongodb.odm.document_manager')->getRepository(MultimediaObject::class);
        $qb = $mmObjRepo->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $qb->field('tracks')->elemMatch(
            $qb->expr()
                ->field('only_audio')->equals($isOnlyAudio)
                ->field('tags')->all(['podcast'])
        );

        return $qb;
    }

    private function getPodcastMultimediaObjectsByAudio($isOnlyAudio = false)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);

        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsByAudioAndSeries($isOnlyAudio, Series $series)
    {
        $qb = $this->createPodcastMultimediaObjectByAudioQueryBuilder($isOnlyAudio);
        $qb->field('series')->references($series);

        return $qb->getQuery()->execute();
    }

    private function getPodcastMultimediaObjectsBySeries(Series $series)
    {
        $mmObjRepo = $this->get('doctrine_mongodb.odm.document_manager')
          ->getRepository(MultimediaObject::class);
        $qb = $mmObjRepo->createStandardQueryBuilder();
        $qb->field('embeddedBroadcast.type')->equals(EmbeddedBroadcast::TYPE_PUBLIC);
        $qb->field('series')->references($series);

        return $qb->getQuery()->execute();
    }

    private function getValues(Request $request, $audioVideoType = 'video', $series = null)
    {
        $container = $this->container;
        $pumukitInfo = $container->getParameter('pumukit.info');

        $values = [];
        $values['base_url'] = $this->getBaseUrl().$request->getBasePath();
        $values['requestURI'] = $values['base_url'].$request->getRequestUri();
        $values['image_url'] = $values['base_url'].'/bundles/pumukitpodcast/images/gc_'.$audioVideoType.'.jpg';
        $values['language'] = $request->getLocale();
        $values['itunes_author'] = $container->getParameter('pumukit_podcast.itunes_author');
        $values['email'] = $pumukitInfo['email'];
        $values['itunes_explicit'] = $container->getParameter('pumukit_podcast.itunes_explicit') ? 'yes' : 'no';

        if ($series) {
            $values['channel_title'] = $series->getTitle();
            $values['channel_description'] = $series->getDescription();
            $values['copyright'] = $series->getCopyright() ? $series->getCopyright() : 'PuMuKIT2 2015';
            $values['itunes_category'] = $series->getProperty('itunescategory') ? $series->getProperty('itunescategory') : $container->getParameter('pumukit_podcast.itunes_category');
            $values['itunes_summary'] = $series->getDescription();
            $values['itunes_subtitle'] = $series->getSubtitle() ? $series->getSubtitle() :
              ($container->getParameter('pumukit_podcast.itunes_subtitle') ? $container->getParameter('pumukit_podcast.itunes_subtitle') : $values['channel_description']);
        } else {
            $values['channel_title'] = $container->getParameter('pumukit_podcast.channel_title') ?
              $container->getParameter('pumukit_podcast.channel_title') :
              $pumukitInfo['title'];
            $values['channel_description'] = $container->getParameter('pumukit_podcast.channel_description') ?
              $container->getParameter('pumukit_podcast.channel_description') :
              $pumukitInfo['description'];
            $values['copyright'] = $container->getParameter('pumukit_podcast.channel_copyright') ?
              $container->getParameter('pumukit_podcast.channel_copyright') :
              ($pumukitInfo['copyright'] ?? 'PuMuKIT2 2015');
            $values['itunes_category'] = $container->getParameter('pumukit_podcast.itunes_category');
            $values['itunes_summary'] = $container->getParameter('pumukit_podcast.itunes_summary') ?
              $container->getParameter('pumukit_podcast.itunes_summary') :
              $values['channel_description'];
            $values['itunes_subtitle'] = $container->getParameter('pumukit_podcast.itunes_subtitle') ?
              $container->getParameter('pumukit_podcast.itunes_subtitle') : $values['channel_description'];
        }

        return $values;
    }

    private function getXMLElement($multimediaObjects, $values, $trackType = 'video')
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                                     .'<rss xmlns:itunes="'.self::ITUNES_DTD_URL
                                     .'" xmlns:itunesu="'.self::ITUNESU_FEED_URL
                                     .'" xmlns:atom="'.self::ATOM_URL
                                     .'" xml:lang="en" version="2.0"></rss>'
                                     );
        $channel = $xml->addChild('channel');
        $atomLink = $channel->addChild('atom:link', null, self::ATOM_URL);
        $atomLink->addAttribute('href', $values['requestURI']);
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');
        $channel->addChild('title', htmlspecialchars($values['channel_title']));
        $channel->addChild('link', $values['base_url']);
        $channel->addChild('description', htmlspecialchars($values['channel_description']));
        $channel->addChild('generator', 'PuMuKiT');
        $channel->addChild('lastBuildDate', (new \DateTime('now'))->format('r'));
        $channel->addChild('language', $values['language']);
        $channel->addChild('copyright', $values['copyright']);

        $itunesImage = $channel->addChild('itunes:image', null, self::ITUNES_DTD_URL);
        $itunesImage->addAttribute('href', $values['image_url']);

        $image = $channel->addChild('image');
        $image->addChild('url', $values['image_url']);
        $image->addChild('title', htmlspecialchars($values['channel_title']));
        $image->addChild('link', $values['base_url']);

        $itunesCategory = $channel->addChild('itunes:category', null, self::ITUNES_DTD_URL);
        $itunesCategory->addAttribute('text', $values['itunes_category']);

        $channel->addChild('itunes:summary', htmlspecialchars($values['itunes_summary']), self::ITUNES_DTD_URL);
        $channel->addChild('itunes:subtitle', htmlspecialchars($values['itunes_subtitle']), self::ITUNES_DTD_URL);
        $channel->addChild('itunes:author', htmlspecialchars($values['itunes_author']), self::ITUNES_DTD_URL);

        $itunesOwner = $channel->addChild('itunes:owner', null, self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:name', $values['itunes_author'], self::ITUNES_DTD_URL);
        $itunesOwner->addChild('itunes:email', $values['email'], self::ITUNES_DTD_URL);

        $channel->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);

        $this->completeTracksInfo($channel, $multimediaObjects, $values, $trackType);

        return $xml;
    }

    private function completeTracksInfo($channel, $multimediaObjects, $values, $trackType = 'video')
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $router = $this->get('router');
        $tagRepo = $dm->getRepository(Tag::class);
        $itunesUTag = $tagRepo->findOneByCod('ITUNESU');

        foreach ($multimediaObjects as $multimediaObject) {
            $track = $this->getPodcastTrack($multimediaObject, $trackType);
            if ($track) {
                $item = $channel->addChild('item');

                $title = (0 === strlen($multimediaObject->getTitle())) ?
                  $multimediaObject->getSeries()->getTitle() :
                  $multimediaObject->getTitle();
                $item->addChild('title', htmlspecialchars($title));
                $item->addChild('itunes:subtitle', htmlspecialchars($multimediaObject->getSubtitle()), self::ITUNES_DTD_URL);
                $item->addChild('itunes:summary', htmlspecialchars($multimediaObject->getDescription()), self::ITUNES_DTD_URL);
                $item->addChild('description', htmlspecialchars($multimediaObject->getDescription()));

                if (null !== $itunesUTag) {
                    foreach ($multimediaObject->getTags() as $tag) {
                        if ($tag->isDescendantOf($itunesUTag)) {
                            $itunesUCategory = $item->addChild('itunesu:category', null, self::ITUNESU_FEED_URL);
                            $itunesUCategory->addAttribute('itunesu:code', $tag->getCod(), self::ITUNESU_FEED_URL);
                        }
                    }
                }

                if ($multimediaObject->isPublished() && $multimediaObject->containsTagWithCod('PUCHWEBTV')) {
                    $link = $router->generate('pumukit_webtv_multimediaobject_index', ['id' => $multimediaObject->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $item->addChild('link', $link);
                }

                $enclosure = $item->addChild('enclosure');
                $enclosure->addAttribute('url', $this->getAbsoluteUrl($track->getUrl()));
                $enclosure->addAttribute('length', $track->getSize());
                $enclosure->addAttribute('type', $track->getMimeType());

                $item->addChild('guid', $this->getAbsoluteUrl($track->getUrl()));
                $item->addChild('itunes:duration', $this->getDurationString($multimediaObject), self::ITUNES_DTD_URL);
                $item->addChild('author', $values['email'].' ('.$values['channel_title'].')');
                $item->addChild('itunes:author', $multimediaObject->getCopyright(), self::ITUNES_DTD_URL);
                $item->addChild('itunes:keywords', htmlspecialchars($multimediaObject->getKeyword()), self::ITUNES_DTD_URL);
                $item->addChild('itunes:explicit', $values['itunes_explicit'], self::ITUNES_DTD_URL);
                $item->addChild('itunes:image', $this->getAbsoluteUrl($multimediaObject->getFirstUrlPic()), self::ITUNES_DTD_URL);
                $item->addChild('pubDate', $multimediaObject->getRecordDate()->format('r'));
            }
            $dm->clear();
        }

        return $channel;
    }

    private function getPodcastTrack(MultimediaObject $multimediaObject, $trackType = 'video')
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
        $video_all_tags = ['podcast'];
        $video_not_all_tags = ['audio'];

        return $multimediaObject->getFilteredTrackWithTags(
                                                           [],
                                                           $video_all_tags,
                                                           [],
                                                           $video_not_all_tags,
                                                           false);
    }

    private function getAudioTrack(MultimediaObject $multimediaObject)
    {
        $audio_all_tags = ['podcast', 'audio'];
        $audio_not_all_tags = [];

        return $multimediaObject->getFilteredTrackWithTags(
                                                           [],
                                                           $audio_all_tags,
                                                           [],
                                                           $audio_not_all_tags,
                                                           false);
    }

    private function getDurationString(MultimediaObject $multimediaObject)
    {
        $minutes = floor($multimediaObject->getDuration() / 60);
        $seconds = $multimediaObject->getDuration() % 60;

        return $minutes.':'.$seconds;
    }

    private function getAbsoluteUrl($url = '')
    {
        if ($url && '/' == $url[0]) {
            return $this->getBaseUrl().$url;
        }

        return $url;
    }

    private function getBaseUrl()
    {
        $context = $this->get('router.request_context');
        if (!$context) {
            throw new \RuntimeException('To generate an absolute URL for an asset, the Symfony Routing component is required.');
        }

        $scheme = $context->getScheme();
        $host = $context->getHost();
        $port = '';
        if ('http' === $scheme && 80 != $context->getHttpPort()) {
            $port = ':'.$context->getHttpPort();
        } elseif ('https' === $scheme && 443 != $context->getHttpsPort()) {
            $port = ':'.$context->getHttpsPort();
        }

        return $scheme.'://'.$host.$port;
    }
}
