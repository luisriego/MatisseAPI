<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurringExpenseItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recurringExpenseId', HiddenType::class)
            ->add('description', HiddenType::class)
            ->add('include', CheckboxType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('dueDate', DateType::class, [
                'label' => false,
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'AAAA-MM-dd',
                ],
                'required' => false,
            ])
            ->add('amount', MoneyType::class, [
                'label' => false,
                'currency' => 'BRL',
                'divisor' => 100,
                'required' => false,
                'attr' => ['class' => 'form-control-sm recurring-expense-amount'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Sem data_class, o formulário trabalhará com um array de dados
        ]);
    }
}
