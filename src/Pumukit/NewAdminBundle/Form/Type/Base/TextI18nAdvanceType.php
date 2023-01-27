<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type\Base;

class TextI18nAdvanceType extends AbstractI18nType
{
    public function getBlockPrefix(): string
    {
        return 'texti18nadvance';
    }
}
