<?php

declare(strict_types=1);

namespace App\Controller\Admin\Expense;

use App\Entity\User;
use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted(User::ROLE_ADMIN)]
final class ExpenseListController extends AbstractController
{
    public function __construct(
        private readonly ExpenseRepository $expenseRepository
    ) {}

    #[Route('/admin/expenses', name: 'admin_expense_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $monthYearString = $request->query->get('month'); // Espera formato: YYYY-MM
        $currentDate = new \DateTimeImmutable();

        $selectedYear = (int) $currentDate->format('Y');
        $selectedMonth = (int) $currentDate->format('n'); // 'n' para mes sin cero inicial

        if ($monthYearString && preg_match('/^(\d{4})-(\d{2})$/', $monthYearString, $matches)) {
            $yearFromQuery = (int) $matches[1];
            $monthFromQuery = (int) $matches[2];

            // Validar que el mes y año sean razonables
            if (checkdate($monthFromQuery, 1, $yearFromQuery)) {
                $selectedYear = $yearFromQuery;
                $selectedMonth = $monthFromQuery;
            } else {
                $this->addFlash('warning', 'Formato de mês/ano inválido na URL. Mostrando o mês corrente.');
            }
        }

        // Ahora $selectedMonth y $selectedYear tienen los valores correctos (del query o del mes actual)
        $expenses = $this->expenseRepository->findByMonth((string)$selectedMonth, (string)$selectedYear);

        // Para la vista, es útil tener el nombre del mes y opciones para un selector
        $targetDateForDisplay = (new \DateTimeImmutable())->setDate($selectedYear, $selectedMonth, 1);
        $formatter = new \IntlDateFormatter(
            $request->getLocale() ?: 'pt_BR',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            \IntlDateFormatter::GREGORIAN,
            'MMMM' // Nombre completo del mes
        );
        $selectedMonthName = ucfirst($formatter->format($targetDateForDisplay));

        // Generar opciones para selectores de mes/año (opcional, pero útil para la UI)
        $monthChoices = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthDate = (new \DateTimeImmutable())->setDate($selectedYear, $m, 1);
            $monthChoices[$m] = ucfirst($formatter->format($monthDate));
        }

        $currentYearForRange = (int) $currentDate->format('Y');
        $yearChoices = range($currentYearForRange - 5, $currentYearForRange + 2);

        return $this->render('admin/expense/list.html.twig', [
            'expenses' => $expenses,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'selectedMonthName' => $selectedMonthName,
            'monthChoices' => $monthChoices, // Para el selector de mes en Twig
            'yearChoices' => $yearChoices,   // Para el selector de año en Twig
            'currentMonthYearString' => $currentDate->format('Y-m'), // Para el enlace "Mes Actual"
        ]);
    }
}
