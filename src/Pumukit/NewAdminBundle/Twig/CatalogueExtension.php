<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CatalogueExtension extends AbstractExtension
{
    private $tagCatalogueService;

    public function __construct(TagCatalogueService $tagCatalogueService)
    {
        $this->tagCatalogueService = $tagCatalogueService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_custom_fields', [$this, 'getAllCustomFields']),
            new TwigFunction('render_object_field', [$this, 'renderObjectField']),
        ];
    }

    public function getAllCustomFields(): array
    {
        return $this->tagCatalogueService->getAllCustomListFields();
    }

    public function renderObjectField(MultimediaObject $object, SessionInterface $session, string $field)
    {
        return $this->tagCatalogueService->renderField($object, $session, $field);
    }
}
