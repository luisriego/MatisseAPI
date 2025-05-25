<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; // CAMBIO: Añadido ChoiceType
// use Symfony\Component\Form\Extension\Core\Type\DateType; // CAMBIO: Eliminado DateType si ya no se usa
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as SymfonyCollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class GenerateSlipsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetMonth', ChoiceType::class, [ // CAMBIO: Tipo de campo
                'label' => 'Mês de Referência para Geração',
                'choices' => $this->generateMonthYearChoices(), // CAMBIO: Opciones generadas dinámicamente
                'attr' => [
                    'class' => 'form-select', // CAMBIO: Clase para Bootstrap 5 select
                    'onchange' => 'this.form.submit()', // Se mantiene
                ],
                'help' => 'Selecione o mês desejado.',
                // 'placeholder' => 'Selecione o mês...', // Opcional: si quieres un valor vacío inicial
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

        $builder->add('submit', SubmitType::class, [
            'label' => 'Gerar Boletos', // Este label é sobrescrito no Twig
            'attr' => ['class' => 'btn btn-primary mt-3'],
        ]);
    }

    private function generateMonthYearChoices(): array
    {
        $choices = [];
        // Considera inyectar la zona horaria o hacerla configurable si es necesario
        $appTimeZone = new \DateTimeZone('America/Sao_Paulo');
        $currentDate = new \DateTimeImmutable('now', $appTimeZone);

        $formatter = new \IntlDateFormatter(
            'pt_BR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $appTimeZone->getName(),
            \IntlDateFormatter::GREGORIAN,
            'MMMM' // Patrón para el nombre completo del mes
        );

        // Define el rango de fechas, por ejemplo: 2 años atrás hasta 1 año en el futuro desde el mes actual
        $startLoopDate = $currentDate->modify('-2 years')->modify('first day of this month');
        $endLoopDate = $currentDate->modify('+1 year')->modify('last day of this month'); // Incluye el mes final

        $period = new \DatePeriod(
            $startLoopDate,
            new \DateInterval('P1M'), // Iterar mes a mes
            $endLoopDate->modify('+1 day') // Asegurar que el último mes del rango se incluya
        );

        $tempChoices = [];
        foreach ($period as $date) {
            /** @var \DateTimeImmutable $date */
            $monthName = ucfirst($formatter->format($date));
            $label = sprintf('%s / %d', $monthName, (int)$date->format('Y'));
            $value = $date->format('Y-m'); // Formato "AAAA-MM"
            $tempChoices[$value] = $label;
        }

        // Ordenar para que los meses más recientes aparezcan primero
        krsort($tempChoices); // Ordena por clave (valor "AAAA-MM") en orden descendente

        // ChoiceType espera [label => value]
        $finalChoices = [];
        foreach ($tempChoices as $value => $label) {
            $finalChoices[$label] = $value;
        }
        return $finalChoices;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

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
            // No necesitamos 'default_target_month_string' aquí, se maneja con $formData en el controlador
        ]);
        $resolver->setAllowedTypes('needs_confirmation', 'bool');
        $resolver->setAllowedTypes('month_to_confirm', ['null', \DateTimeInterface::class]);
        $resolver->setAllowedTypes('existing_slips_count', 'int');
    }
}
