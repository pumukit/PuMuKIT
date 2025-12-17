<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class UpdateGroupController extends AbstractController
{
    public function __construct() {}

    public function __invoke()
    {
        return new Response('UpdateGroupController works!');
    }
}
