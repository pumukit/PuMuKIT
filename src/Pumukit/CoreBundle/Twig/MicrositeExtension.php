<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\CoreBundle\Services\MicrositeService;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class MicrositeExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly DocumentManager $documentManager,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('microsite', [$this, 'microsite']),
            new TwigFunction('microsite_tag', [$this, 'micrositeTag']),
        ];
    }

    public function microsite(): ?array
    {
        $microsite = $this->requestStack->getMainRequest()?->attributes->get(MicrositeService::ATTRIBUTE);

        return is_array($microsite) ? $microsite : null;
    }

    public function micrositeTag(): ?Tag
    {
        $microsite = $this->microsite();
        if (null === $microsite) {
            return null;
        }

        $tag = $this->documentManager->getRepository(Tag::class)->findOneBy(['cod' => $microsite['tag']]);

        return $tag instanceof Tag ? $tag : null;
    }
}
