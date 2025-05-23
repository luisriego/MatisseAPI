<?php

namespace App\Form\Admin;

// Mantenha os uses existentes
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as SymfonyCollectionType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView; // Adicionar este use
use Symfony\Component\Form\FormInterface; // Adicionar este use

class GenerateSlipsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetMonth', DateType::class, [
                'label' => 'Mês de Referência para Geração',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'AAAA-MM',
                ],
                'help' => 'Selecione o mês desejado.',
            ])
            ->add('recurringExpenses', SymfonyCollectionType::class, [
                'entry_type' => RecurringExpenseItemFormType::class,
                'entry_options' => ['label' => false],
                'allow_add' => false,
                'allow_delete' => false,
                'label' => false,
                'by_reference' => false,
                'attr' => ['class' => 'recurring-expenses-collection'],
            ]);

        if ($options['needs_confirmation']) {
            $builder->add('confirm_regeneration', CheckboxType::class, [
                'label' => sprintf(
                    'Sim, desejo regerar os %d boletos para %s.',
                    $options['existing_slips_count'],
                    $options['month_to_confirm'] ? $options['month_to_confirm']->format('F Y') : 'o mês selecionado'
                ),
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Assert\IsTrue(['message' => 'Você deve confirmar a regeração para prosseguir.']),
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => 'Gerar Boletos',
            'attr' => ['class' => 'btn btn-primary mt-3'],
        ]);
    }

    // ADICIONAR/MODIFICAR ESTE MÉTODO
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options); // Chama o buildView pai, se houver

        // Passa explicitamente as opções para as variáveis da view
        $view->vars['needs_confirmation'] = $options['needs_confirmation'];
        $view->vars['month_to_confirm'] = $options['month_to_confirm'];
        $view->vars['existing_slips_count'] = $options['existing_slips_count'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'needs_confirmation' => false,
            'month_to_confirm' => null,
            'existing_slips_count' => 0,
        ]);
        $resolver->setAllowedTypes('needs_confirmation', 'bool');
        $resolver->setAllowedTypes('month_to_confirm', ['null', \DateTimeInterface::class]);
        $resolver->setAllowedTypes('existing_slips_count', 'int');
    }
}
