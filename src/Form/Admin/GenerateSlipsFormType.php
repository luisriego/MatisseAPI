<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class GenerateSlipsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetMonth', DateType::class, [
                'label' => 'Mês de Referência',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'string',
                'format' => 'yyyy-MM-dd',
                'data' => date('Y-m-d'),
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Por favor, selecione um mês.']),
                ],
                'help' => 'Selecione o primeiro dia do mês para o qual deseja gerar os boletos. Exemplo, 01/05/2025 para gerar em junho os boletos de maio.',
            ]);

        // Este campo solo se mostrará/usará si es necesario confirmar
        if ($options['needs_confirmation']) {
            $builder->add('confirm_regeneration', CheckboxType::class, [
                'label' => sprintf(
                    'Sim, confirmo a regeração de %d boleto(s) para %s.',
                    $options['existing_slips_count'],
                    $options['month_to_confirm'] ? $options['month_to_confirm']->format('F Y') : ''
                ),
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Você deve confirmar a regeração.']),
                ],
                'attr' => ['class' => 'form-check-input'],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => $options['needs_confirmation'] ? 'Confirmar e Gerar Boletos' : 'Gerar Boletos',
            'attr' => ['class' => 'btn btn-primary mt-3'],
        ]);
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

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options); // Buena práctica llamar al padre
        $view->vars['needs_confirmation'] = $options['needs_confirmation'];
        $view->vars['month_to_confirm'] = $options['month_to_confirm'];
        $view->vars['existing_slips_count'] = $options['existing_slips_count'];
    }
}
