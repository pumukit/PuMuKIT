<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type\Base;

class TextI18nType extends AbstractI18nType
{
    public function getBlockPrefix()
    {
        return 'texti18n';
    }
}
