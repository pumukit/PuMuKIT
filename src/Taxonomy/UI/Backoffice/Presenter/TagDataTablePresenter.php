<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Presenter;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class TagDataTablePresenter
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function present(Tag $tag): array
    {
        return [
            'id' => $tag->getId(),
            'parent_id' => $tag->getParent() ? $tag->getParent()->getId() : null,
            'cod' => $tag->getCod(),
            'title' => $tag->getTitle('en'),
            'display' => $tag->getDisplay(),
            'metatag' => $tag->getMetatag(),
            'level' => $tag->getLevel() ?? 0,
            'number_multimedia_objects' => $tag->getNumberMultimediaObjects(),
            'number_children' => $tag->getNumberOfChildren() ?? 0,
            'actions' => $this->generateActions($tag),
        ];
    }

    private function generateActions(Tag $tag): string
    {
        $updateUrl = $this->urlGenerator->generate('taxonomy_tag_update', ['id' => $tag->getId()]);
        $deleteUrl = $this->urlGenerator->generate('taxonomy_tag_delete', ['id' => $tag->getId()]);

        return sprintf(
            '<div class="d-flex gap-1 justify-content-end"> '.
            '<a href="%s" class="btn btn-sm btn-info"><i class="fa fa-pencil"></i> Update</a> ' .
            '<button class="btn btn-sm btn-danger" onclick="deleteTag(\'%s\', \'%s\')" title="Delete"><i class="fa fa-trash"></i> Delete</button>'
            . '</div>',
            $updateUrl,
            $tag->getId(),
            $deleteUrl
        );
    }
}

