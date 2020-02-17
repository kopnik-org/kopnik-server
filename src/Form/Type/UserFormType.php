<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', null, ['attr' => ['autofocus' => true], 'required' => true])
            ->add('patronymic')
            ->add('last_name', null, ['required' => true])
            ->add('locale')
            ->add('birth_year')
            ->add('passport_code')
            ->add('latitude', TextType::class,  ['attr' => ['placeholder' => 'Latitude']])
            ->add('longitude', TextType::class, ['attr' => ['placeholder' => 'Longitude']])
            ->add('update', SubmitType::class,  ['attr' => ['class' => 'btn-success']])
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
