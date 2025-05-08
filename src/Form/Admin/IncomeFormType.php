<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\IncomeType;
use App\Entity\Resident;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class IncomeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('incomeTypeId', EntityType::class, [
                'class' => IncomeType::class,
                'choice_label' => function (IncomeType $incomeType) {
                    return sprintf('%s', $incomeType->name());
                },
                'choice_value' => 'id',
                'label' => 'Tipo de receita',
                'placeholder' => 'Selecione um tipo de receita',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Receita de condomínio',
                ],
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Importe',
                'currency' => 'BRL',
                'divisor' => 100,
                'scale' => 0,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\PositiveOrZero(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 70,00',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['min' => 3, 'max' => 255]),
                ],
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Deixar em branco se não houver descrição',
                ],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Data de Vencimento',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'string',
                'format' => 'yyyy-MM-dd',
                'data' => date('Y-m-d'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Date(),
                ],
            ])
            ->add('residentId', EntityType::class, [
                'class' => Resident::class,
                'choice_label' => function (Resident $resident) {
                    return sprintf('%s', $resident->unit());
                },
                'choice_value' => 'id',
                'label' => 'Morador',
                'placeholder' => 'Selecione um morador',
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvar receita',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}