<?php

namespace App\User\UI\Backend\Controller;

use App\User\Application\GetUserListHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserListController extends AbstractController
{
    public function __construct(protected GetUserListHandler $handler) {}

    /**
     * @Route ("/admin/backend/users", name="admin_users_list")
     */
    public function __invoke(Request $request): Response
    {
        $filters = [
            'username' => $request->query->get('username'),
            'email' => $request->query->get('email'),
            'name' => $request->query->get('name'),
            'permissionProfile' => $request->query->get('permissionProfile'),
            'origin' => $request->query->get('origin'),
        ];

        $users = $this->handler->handle($filters);

        return $this->render('@User/UI/Backend/Pages/list.html.twig', [
            'users' => $users,
            'filters' => $filters,
        ]);
    }
}
