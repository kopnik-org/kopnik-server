<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['attr' => ['autofocus' => true], 'required' => true])
            ->add('patronymic')
            ->add('lastname', null, ['required' => true])
            ->add('birth_year')
            ->add('passport_code')
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-success']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user';
    }
}
