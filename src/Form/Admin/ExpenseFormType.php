<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Account;
use App\Entity\ExpenseType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ExpenseFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label' => 'Valor',
                'currency' => 'BRL',
                'divisor' => 100,
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
                ],
            ])
            ->add('date', DateType::class, [
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
            ->add('type', EntityType::class, [
                'class' => ExpenseType::class,
                'choice_label' => function (ExpenseType $type) {
                    return sprintf('%s - %s', $type->name(), $type->description());
                },
                'choice_value' => 'id',
                'label' => 'Tipo de Despesa',
                'placeholder' => 'Selecione o tipo de despesa...', // Texto opcional para la primera opción vacía
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('paidFromAccountId', EntityType::class, [ // Campo para seleccionar la Cuenta
                'class' => Account::class,
                'choice_label' => function (Account $account) {
                    return sprintf('%s', $account->name()); // Muestra nombre y código
                },
                'choice_value' => 'id', // El valor enviado será el ID (UUID string) de la cuenta
                'label' => 'Pagar da Conta',
                'placeholder' => 'Selecione a conta...',
                'required' => true,
                'constraints' => [
                ],
                // Puedes añadir 'query_builder' si necesitas ordenar o filtrar
                // 'query_builder' => function (AccountRepository $ar) {
                //     return $ar->createQueryBuilder('a')
                //         ->where('a.isActive = :active') // Ejemplo: solo cuentas activas
                //         ->setParameter('active', true)
                //         ->orderBy('a.name', 'ASC');
                // },
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvar despesa',
                'attr' => ['class' => 'btn btn-primary mt-3'], // Clases CSS opcionales
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
