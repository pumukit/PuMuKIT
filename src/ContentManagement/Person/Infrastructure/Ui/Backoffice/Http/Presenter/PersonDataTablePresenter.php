<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\Ui\Backoffice\Http\Presenter;

use Pumukit\SchemaBundle\Document\Person;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class PersonDataTablePresenter
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly Environment $twig,
    ) {}

    public function present(Person $person): array
    {
        return [
            'id' => $person->getId(),
            'name' => $person->getName(),
            'email' => $person->getEmail(),
            'phone' => $person->getPhone(),
            'web' => $person->getWeb(),
            'honorific' => $person->getHonorific('en'),
            'firm' => $person->getFirm('en'),
            'post' => $person->getPost('en'),
            'actions' => $this->renderActions($person),
        ];
    }

    private function renderActions(Person $person): string
    {
        $viewButton = $this->twig->render('@Shared/Views/components/table/buttons/_view_button.html.twig', [
            'url' => $this->router->generate('person_view', ['id' => $person->getId()]),
        ]);

        $editButton = $this->twig->render('@Shared/Views/components/table/buttons/_edit_button.html.twig', [
            'url' => $this->router->generate('person_update', ['id' => $person->getId()]),
        ]);

        $deleteButton = $this->twig->render('@Shared/Views/components/table/buttons/_delete_button.html.twig', [
            'url' => $this->router->generate('person_delete', ['id' => $person->getId()]),
            'confirm' => 'Are you sure you want to remove this person?',
        ]);

        return $viewButton.$editButton.$deleteButton;
    }
}
