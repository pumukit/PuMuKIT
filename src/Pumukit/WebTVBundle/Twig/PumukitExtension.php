<?php

namespace Pumukit\WebTVBundle\Twig;

use Symfony\Component\Routing\RequestContext;

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

    public function __construct(RequestContext $context, $defaultPic)
    {
        $this->context = $context;
        $this->defaultPic = $defaultPic;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('first_url_pic', array($this, 'getFirstUrlPicFilter')),
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

    public function getName()
    {
        return 'pumukit_extension';
    }
}