<?php

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Form;

use App\IdentityAndAccess\Group\Application\Update\UpdateGroupRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('key', TextType::class, ['required' => true])
            ->add('name', TextType::class, ['required' => true])
            ->add('origin', ChoiceType::class, [
                'choices' => [
                    'Local' => 'local',
                    'CAS' => 'cas',
                    'LDAP' => 'ldap',
                    'Other' => 'other',
                ],
            ])
            ->add('comments', TextareaType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateGroupRequest::class,
        ]);
    }
}
