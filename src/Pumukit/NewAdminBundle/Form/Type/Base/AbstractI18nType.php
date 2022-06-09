<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractI18nType extends AbstractType
{
    private $locales;
    private $translators;

    public function __construct(array $locales = [], array $translators = [])
    {
        $this->locales = $locales;
        $this->translators = $translators;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $this->addChildren($event->getForm(), $event->getData());
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'multiple' => false,
                'compound' => true,
                'allow_extra_fields' => true,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['locales'] = $this->locales;
        $view->vars['translators'] = $this->translators;
    }

    public function addChildren(FormInterface $form, $data)
    {
        if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (!is_array($value)) {
                    $form->add($name);
                } else {
                    $form->add($name, null, [
                        'compound' => true,
                    ]);

                    $this->addChildren($form->get($name), $value);
                }
            }
        } else {
            $form->add($data, null, [
                'compound' => false,
            ]);
        }
    }
}
