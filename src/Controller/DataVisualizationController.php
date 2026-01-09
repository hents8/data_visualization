<?php
namespace App\Controller;

use App\Service\ExcelSurveyReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DataVisualizationController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, ExcelSurveyReader $reader)
    {
        $questions = ['Media', 'Medium', 'Secteur', 'Citée', 'Tonalité'];

        // Récupération des filtres
        $yearLeft   = $request->query->get('year_left');
        $monthLeft  = $request->query->get('month_left');
        $yearRight  = $request->query->get('year_right');
        $monthRight = $request->query->get('month_right');

        $selectedYearLeft   = ($yearLeft !== null && $yearLeft !== '') ? (int)$yearLeft : null;
        $selectedMonthLeft  = ($monthLeft !== null && $monthLeft !== '') ? (int)$monthLeft : null;
        $selectedYearRight  = ($yearRight !== null && $yearRight !== '') ? (int)$yearRight : null;
        $selectedMonthRight = ($monthRight !== null && $monthRight !== '') ? (int)$monthRight : null;

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $years = $reader->getYears();
        $dataArray = $reader->getDataArray();
        $questionsHeaders = $reader->getQuestions();
        $yearIndex  = array_search('Year', $questionsHeaders, true);
        $monthIndex = array_search('Month', $questionsHeaders, true);

        // === Résultats panels ===
        $resultsLeft  = [];
        $resultsRight = [];
        foreach ($questions as $q) {
            $resultsLeft[$q]  = $reader->getQuestionResultFromArray($q, $dataArray, $selectedYearLeft, $selectedMonthLeft);
            $resultsRight[$q] = $reader->getQuestionResultFromArray($q, $dataArray, $selectedYearRight, $selectedMonthRight);
        }

        // === Monthly totals pour graphe temporel par année ===
		$monthlyTotalsByYear = [];

		foreach ($dataArray as $row) {
			$rowYear  = (int)($row[$yearIndex] ?? 0);
			$rowMonth = (int)($row[$monthIndex] ?? 0);

			if ($rowMonth < 1 || $rowMonth > 12) continue;

			if (!isset($monthlyTotalsByYear[$rowYear])) {
				$monthlyTotalsByYear[$rowYear] = array_fill(1, 12, 0);
			}

			$monthlyTotalsByYear[$rowYear][$rowMonth]++;
		}

        return $this->render('dashboard/index.html.twig', [
            'resultsLeft'        => $resultsLeft,
            'resultsRight'       => $resultsRight,
            'months'             => $months,
            'years'              => $years,
            'selectedYearLeft'   => $selectedYearLeft,
            'selectedMonthLeft'  => $selectedMonthLeft,
            'selectedYearRight'  => $selectedYearRight,
            'selectedMonthRight' => $selectedMonthRight,
            'monthlyTotalsByYear'=> $monthlyTotalsByYear,
        ]);
    }
}
