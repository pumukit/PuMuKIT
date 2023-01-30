<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type\Base;

class TextareaI18nType extends AbstractI18nType
{
    public function getBlockPrefix(): string
    {
        return 'textareai18n';
    }
}
