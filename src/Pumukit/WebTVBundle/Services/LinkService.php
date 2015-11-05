<?php

namespace Pumukit\WebTVBundle\Services;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LinkService
{
    private $generator;
    private $linkTagToSearch;

    public function __construct(UrlGeneratorInterface $generator, $linkTagToSearch)
    {
        $this->generator = $generator;
        $this->linkTagToSearch = $linkTagToSearch;
    }

    public function generatePathToTag($tagCod = null, $onlyGeneral = null)
    {
        $parameters = array();
        $relative = true;
        if($this->linkTagToSearch) {
            if($tagCod) {
                $parameters['blockedTagCod'] = $tagCod;
            }
            if($onlyGeneral) {
                $parameters['useBlockedTagAsGeneral'] = $onlyGeneral;
            }
            return $this->generator->generate('pumukit_webtv_search_multimediaobjects', $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
        } else {
            if($tagCod) {
                $parameters['cod'] = $tagCod;
            }
            if($onlyGeneral) {
                $parameters['list_only_general'] = $onlyGeneral;
            }
            return $this->generator->generate('pumukit_webtv_bytag_multimediaobjects', $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
        }
    }
}
