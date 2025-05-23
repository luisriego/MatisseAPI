<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\ExpenseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType; // Para isRecurring
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;   // Para distributionMethod
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExpenseTypeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Código Único',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'O código não pode estar vazio.']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 7,
                        'minMessage' => 'O código deve ter pelo menos {{ limit }} caracteres.',
                        'maxMessage' => 'O código não pode ter mais de {{ limit }} caracteres.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: AF1DB, SP1EL, OT1DA',
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nome do Tipo de Despesa',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'O nome não pode estar vazio.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'O nome deve ter pelo menos {{ limit }} caracteres.',
                        'maxMessage' => 'O nome não pode ter mais de {{ limit }} caracteres.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Limpeza, Manutenção, Administrativo',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Descrição (Opcional)',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'A descrição não pode ter mais de {{ limit }} caracteres.',
                    ]),
                ],
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Forneça uma breve descrição sobre este tipo de despesa.',
                ],
            ])
            ->add('distributionMethod', ChoiceType::class, [
                'label' => 'Método de Distribuição',
                'required' => true,
                'choices' => [
                    'Igualitário' => ExpenseType::EQUAL,
                    'Por Fração Ideal' => ExpenseType::FRACTION,
                    'Individual (Não distribui)' => ExpenseType::INDIVIDUAL,
                ],
                'placeholder' => 'Selecione um método', // Opcional
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Selecione um método de distribuição.']),
                    new Assert\Choice([ // Asegura que el valor esté entre las opciones válidas
                        'choices' => [ExpenseType::EQUAL, ExpenseType::FRACTION, ExpenseType::INDIVIDUAL],
                        'message' => 'Método de distribuição inválido.',
                    ]),
                ],
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label' => 'Este tipo de despesa é tipicamente recorrente?',
                'required' => false, // Checkboxes no marcados envían null o false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvar Tipo de Despesa',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExpenseType::class,
        ]);
    }
}
