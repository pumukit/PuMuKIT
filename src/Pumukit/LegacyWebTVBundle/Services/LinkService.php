<?php

namespace Pumukit\LegacyWebTVBundle\Services;

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

    public function generatePathToTag($tagCod = null, $onlyGeneral = null, array $parameters = array())
    {
        if ($tagCod) {
            $parameters['tagCod'] = $tagCod;
        }
        if ($onlyGeneral) {
            $parameters['useTagAsGeneral'] = $onlyGeneral;
        }

        if ($this->linkTagToSearch) {
            return $this->generator->generate('pumukit_webtv_search_multimediaobjects', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        } else {
            return $this->generator->generate('pumukit_webtv_bytag_multimediaobjects', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        }
    }
}
