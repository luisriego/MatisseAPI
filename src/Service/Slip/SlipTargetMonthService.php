<?php

namespace App\Service\Slip;

use Symfony\Component\HttpFoundation\Request;
use DateTimeImmutable;
use DateTimeZone;

class SlipTargetMonthService
{
    /**
     * Determines the target month for slip generation based on the request.
     *
     * @param Request $request The current HTTP request.
     * @return DateTimeImmutable The determined target month, set to the first day at 12:00:00.
     */
    public function determine(Request $request): DateTimeImmutable
    {
        $baseDateStringForParsing = null;
        $appTimeZone = new DateTimeZone('America/Sao_Paulo');
        // Assumed form name based on GenerateSlipsFormType class name
        $formName = 'generate_slips_form';

        if ($request->isMethod('POST')) {
            $submittedData = $request->request->all($formName);
            if (is_string($submittedData['targetMonth']) && !empty($submittedData['targetMonth'])) {
                $monthYearString = $submittedData['targetMonth'];
                // Expects "YYYY-MM" format from the ChoiceType
                if (preg_match('/^\d{4}-\d{2}$/', $monthYearString)) {
                    $baseDateStringForParsing = $monthYearString . '-01'; // Convert "YYYY-MM" to "YYYY-MM-01"
                }
            }
        }

        if ($baseDateStringForParsing === null && $request->query->has('targetMonthInput')) {
            $monthYearString = $request->query->getString('targetMonthInput'); // This is "YYYY-MM"
            if (preg_match('/^\d{4}-\d{2}$/', $monthYearString)) {
                $baseDateStringForParsing = $monthYearString . '-01'; // Convert "YYYY-MM" to "YYYY-MM-01"
            }
        }

        if ($baseDateStringForParsing) {
            try {
                // $baseDateStringForParsing is "YYYY-MM-01"
                $date = new DateTimeImmutable($baseDateStringForParsing, $appTimeZone);
                return $date->modify('first day of this month')->setTime(12, 0, 0);
            } catch (\Exception $e) {
                // Error parsing date, proceed to default date calculation.
                // Original code had an error_log here, which can be re-added if necessary for debugging.
                // error_log("Error parsing date in SlipTargetMonthService::determine: " . $e->getMessage() . " | Input: " . $baseDateStringForParsing);
            }
        }

        // Default date calculation
        $currentDateTime = new DateTimeImmutable('now', $appTimeZone);
        $day = (int)$currentDateTime->format('d');
        $initialDefaultMonth = (int)$currentDateTime->format('n');
        $initialDefaultYear = (int)$currentDateTime->format('Y');

        // If the current day is before the 9th, default to the previous month.
        if ($day < 9) {
            $lastMonthDateTime = $currentDateTime->modify('last month');
            $initialDefaultMonth = (int)$lastMonthDateTime->format('n');
            $initialDefaultYear = (int)$lastMonthDateTime->format('Y');
        }

        return (new DateTimeImmutable('now', $appTimeZone))
            ->setDate($initialDefaultYear, $initialDefaultMonth, 1)
            ->setTime(12, 0, 0);
    }
}
