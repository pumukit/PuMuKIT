<?php

namespace Pumukit\TemplateBundle\Services;

use Pumukit\NewAdminBundle\Menu\ItemInterface;

class MenuService implements ItemInterface
{
    public function getName()
    {
        return 'Edit Templates';
    }

    public function getUri()
    {
        return 'pumukit_template_crud_index';
    }

    public function getAccessRole()
    {
        return 'ROLE_ACCESS_TEMPLATES';
    }
}
