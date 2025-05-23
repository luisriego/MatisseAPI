<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Account;
use App\Entity\ExpenseType;
use App\Entity\RecurringExpense;
use App\Repository\AccountRepository;
use App\Repository\ExpenseTypeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RecurringExpenseFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Descrição da Despesa Recorrente',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 3, 'max' => 255]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Seguro Condomínio Anual',
                ],
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Valor (por ocorrência)',
                'currency' => 'BRL',
                'divisor' => 100,
                'required' => false,
                'constraints' => [
//                    new Assert\NotBlank(),
//                    new Assert\PositiveOrZero(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 150,00',
                ],
            ])
            ->add('expenseType', EntityType::class, [
                'class' => ExpenseType::class,
                'choice_label' => function (ExpenseType $type) {
                    return sprintf('%s - %s', $type->name(), $type->description());
                },
                'label' => 'Tipo de Despesa',
                'placeholder' => 'Selecione o tipo de despesa...',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choice_value' => 'id',
                'query_builder' => function (ExpenseTypeRepository $er) {
                    return $er->createQueryBuilder('et')
                        ->where('et.isRecurring = :isRecurring')
                        ->setParameter('isRecurring', true)
                        ->orderBy('et.description', 'ASC'); // O 'et.name' o 'et.code'
                },
            ])
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'choice_label' => function (Account $account) {
                    return sprintf('%s - %s', $account->name(), $account->description());
                },
                'label' => 'Conta',
                'placeholder' => 'Selecione a conta...',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'choice_value' => 'id',
                'query_builder' => function (AccountRepository $ar) { // Corrected type hint
                    return $ar->createQueryBuilder('a')
                        // ->where('a.isActive = :isActive') // Example: if you only want active accounts
                        // ->setParameter('isActive', true)
                        ->orderBy('a.name', 'ASC'); // Removed the 'isRecurring' condition
                },
            ])
            ->add('frequency', ChoiceType::class, [
                'label' => 'Frequência',
                'choices' => [
                    'Mensal' => RecurringExpense::FREQUENCY_MONTHLY,
                    'Bimestral' => RecurringExpense::FREQUENCY_BIMONTHLY,
                    'Trimestral' => RecurringExpense::FREQUENCY_QUARTERLY,
                    'Semestral' => RecurringExpense::FREQUENCY_SEMIANNUALLY,
                    'Anual' => RecurringExpense::FREQUENCY_ANNUALLY,
                ],
                'placeholder' => 'Selecione a frequência...',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ])
            ->add('dueDay', IntegerType::class, [
                'label' => 'Dia do Vencimento/Ocorrência no Mês',
                'required' => true, // Puede ser false si la lógica lo permite para ciertas frecuencias
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Range(['min' => 1, 'max' => 31]),
                ],
                'attr' => [
                    'placeholder' => 'Dia (1-31)',
                    'min' => 1,
                    'max' => 31,
                ],
                'help' => 'Dia do mês em que a despesa ocorre ou vence. Ex: 5 para o dia 5.',
            ])
            ->add('monthsOfYear', ChoiceType::class, [
                'label' => 'Meses de Ocorrência (para Semestral/Anual)',
                'choices' => [
                    'Janeiro' => 1, 'Fevereiro' => 2, 'Março' => 3, 'Abril' => 4,
                    'Maio' => 5, 'Junho' => 6, 'Julho' => 7, 'Agosto' => 8,
                    'Setembro' => 9, 'Outubro' => 10, 'Novembro' => 11, 'Dezembro' => 12,
                ],
                'multiple' => true,
                'expanded' => true, // Muestra como checkboxes, puedes poner false para un select múltiple
                'required' => false, // Solo relevante para ciertas frecuencias
                'help' => 'Selecione os meses específicos se a frequência for Semestral ou Anual.',
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Data de Início da Recorrência',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable', // La entidad usa DateTimeInterface, pero el input puede ser datetime_immutable
                // o datetime, dependiendo de cómo quieras manejarlo.
                // Si startDate en la entidad es DateTime, usa 'datetime'
                'format' => 'yyyy-MM-dd',
                'data' => new \DateTimeImmutable(), // Valor por defecto
                'constraints' => [
//                    new Assert\NotBlank(),
//                    new Assert\Date(),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Data de Fim da Recorrência (Opcional)',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable', // Igual que startDate
                'format' => 'yyyy-MM-dd',
                'required' => false,
                'constraints' => [
//                    new Assert\Date(),
//                    new Assert\Expression(
//                        "this.getParent().get('startDate').getData() === null or value === null or value >= this.getParent().get('startDate').getData()",
//                        message: "A data final não pode ser anterior à data inicial."
//                    )
                ],
                'help' => 'Deixe em branco se a recorrência for indefinida.',
            ])
            ->add('occurrencesLeft', IntegerType::class, [
                'label' => 'Número de Ocorrências Restantes (Opcional)',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
                'attr' => [
                    'placeholder' => 'Ex: 12 (para 12 ocorrências)',
                ],
                'help' => 'Use se a recorrência ocorre um número fixo de vezes, em vez de uma data final.',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Ativa?',
                'required' => false,
                'data' => true,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notas e Observações (Opcional)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Salvar Despesa Recorrente',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecurringExpense::class,
        ]);
    }
}
