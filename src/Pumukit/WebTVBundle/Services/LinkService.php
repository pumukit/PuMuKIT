<?php

namespace Pumukit\WebTVBundle\Services;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class LinkService.
 */
class LinkService
{
    private $generator;
    private $linkTagToSearch;

    /**
     * LinkService constructor.
     *
     * @param string $linkTagToSearch
     */
    public function __construct(UrlGeneratorInterface $generator, $linkTagToSearch)
    {
        $this->generator = $generator;
        $this->linkTagToSearch = $linkTagToSearch;
    }

    /**
     * @param string|null $tagCod
     * @param string|null $onlyGeneral
     *
     * @return string
     */
    public function generatePathToTag($tagCod = null, $onlyGeneral = null, array $parameters = [])
    {
        if ($tagCod) {
            $parameters['tagCod'] = $tagCod;
        }
        if ($onlyGeneral) {
            $parameters['useTagAsGeneral'] = $onlyGeneral;
        }

        if ($this->linkTagToSearch) {
            return $this->generator->generate('pumukit_webtv_search_multimediaobjects', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
        }

        return $this->generator->generate('pumukit_webtv_bytag_multimediaobjects', $parameters, UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
