<?php

namespace App\Form;

use App\Entity\CreditCardEdit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{NumberType, TextareaType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditCardEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('annualCharges', NumberType::class, [
                'label' => 'Annual Charges',
                'required' => true,
                'scale' => 2,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreditCardEdit::class,
        ]);
    }
}
