<?php

namespace Pumukit\WebTVBundle\Twig;

use Symfony\Component\Routing\RequestContext;
use Pumukit\SchemaBundle\Document\Broadcast;
use Doctrine\ODM\MongoDB\DocumentManager;

class PumukitExtension extends \Twig_Extension
{

    /**
     * @var string
     */
    protected $defaultPic;

    /**
     * @var RequestContext 
     */
    protected $context;

    private $dm;

    public function __construct(DocumentManager $documentManager, RequestContext $context, $defaultPic)
    {
        $this->dm = $documentManager;
        $this->context = $context;
        $this->defaultPic = $defaultPic;
    }

    public function getName()
    {
        return 'pumukit_extension';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('first_url_pic', array($this, 'getFirstUrlPicFilter')),
            new \Twig_SimpleFilter('precinct_fulltitle', array($this, 'getPrecinctFulltitle')),
            new \Twig_SimpleFilter('count_multimedia_objects', array($this, 'countMultimediaObjects')),
        );
    }

    /**
     * Get functions
     */
    public function getFunctions()
    {
        return array(
                     new \Twig_SimpleFunction('public_broadcast', array($this, 'getPublicBroadcast')),
                     new \Twig_SimpleFunction('precinct', array($this, 'getPrecinct')),
                     new \Twig_SimpleFunction('precinct_of_series', array($this, 'getPrecinctOfSeries')),
                     );
    }

    /**
     *
     * @param Series|MultimediaObject $object    Object to get the url (using $object->getPics())
     * @param boolean                 $absolute  return absolute path.
     *
     * @return string
     */
    public function getFirstUrlPicFilter($object, $absolute=false)
    {
      $pics = $object->getPics();
      if(0 == count($pics)) {
          $picUrl = $this->defaultPic;
      }else{
          $pic = $pics[0];
          $picUrl = $pic->getUrl();
      }

      if($absolute && "/" == $picUrl[0]) {
          $scheme = $this->context->getScheme();
          $host = $this->context->getHost();
          $port = '';
          if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
              $port = ':'.$this->context->getHttpPort();
          } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
              $port = ':'.$this->context->getHttpsPort();
          }

          return $scheme."://".$host.$port.$picUrl;
      }

      return $picUrl;
        

    }

    /**
     * Get public broadcast
     *
     * @return string
     */
    public function getPublicBroadcast()
    {
        return Broadcast::BROADCAST_TYPE_PUB;
    }

    /**
     * Get precinct
     *
     * @param ArrayCollection $embeddedTags
     * @return EmbbededTag|null
     */
    public function getPrecinct($embeddedTags)
    {
        $precinctTag = null;

        foreach ($embeddedTags as $tag) {
            if ((0 === strpos($tag->getCod(), 'PRECINCT')) && ('PRECINCT' !== $tag->getCod())) {
                return $tag;
            }
        }

        return $precinctTag;
    }

    /**
     * Get precinct of Series
     *
     * @param ArrayCollection $multimediaObjects
     * @return EmbbededTag|null
     */
    public function getPrecinctOfSeries($multimediaObjects)
    {
        $precinctTag = false;
        $precinctCode = null;
        $first = true;
        foreach ($multimediaObjects as $multimediaObject) {
            if ($first) {
                $precinctTag = $this->getPrecinct($multimediaObject->getTags());
                if (!$precinctTag) return false;
                $precinctCode = $precinctTag->getCod();
                $first = false;
            } else {
                $precinctTag = $this->getPrecinct($multimediaObject->getTags());
                if (!$precinctTag) return false;
                if ($precinctCode != $precinctTag->getCod()) {
                    return false;
                }
            }
        }

        return $precinctTag;
    }

    /**
     * Get precinct fulltitle
     *
     * @param EmbbededTag $precinctEmbeddedTag
     * @return string
     */
    public function getPrecinctFulltitle($precinctEmbeddedTag)
    {
        $fulltitle = '';

        if ($precinctEmbeddedTag) {
            $tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
            $precinctTag = $tagRepo->findOneByCod($precinctEmbeddedTag->getCod());
            if ($precinctTag) {
                if ($precinctTag->getTitle()) {
                    $fulltitle = $precinctTag->getTitle();
                }
                $placeTag = $precinctTag->getParent();
                if ($placeTag) {
                    if ($placeTag->getTitle()) {
                        if ($fulltitle) {
                            $fulltitle .= ', ' . $placeTag->getTitle();
                        } else {
                            $fulltitle = $placeTag->getTitle();
                        }
                    }
                }
            } elseif ($precinctEmbeddedTag->getTitle()) {
                $fulltitle = $precinctEmbeddedTag->getTitle();
            }
        }

        return $fulltitle;
    }

    /**
     * Count Multimedia Objects
     *
     * @param Series $series
     * @return integer
     */
    public function countMultimediaObjects($series)
    {
        return $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->countInSeries($series);
    }
}