<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\Presenter;

use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class TagDataTablePresenter
{
    public function __construct(
        private RouterInterface $router,
        private Environment $twig,
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
            'actions' => $this->renderActions($tag),
        ];
    }

    private function renderActions(Tag $tag): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('taxonomy_tag_view', ['id' => $tag->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('taxonomy_tag_update', ['id' => $tag->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('taxonomy_tag_delete', ['id' => $tag->getId()]),
            'confirm' => 'Are you sure you want to remove this tag?',
        ]);

        return $viewButton . $editButton . $deleteButton;
    }
}
