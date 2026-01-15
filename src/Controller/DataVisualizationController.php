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

        /* =======================
         * Filtres panels
         * ======================= */
        $selectedYearLeft   = $request->query->get('year_left')  !== '' ? (int)$request->query->get('year_left')  : null;
        $selectedMonthLeft  = $request->query->get('month_left') !== '' ? (int)$request->query->get('month_left') : null;
        $selectedYearRight  = $request->query->get('year_right') !== '' ? (int)$request->query->get('year_right') : null;
        $selectedMonthRight = $request->query->get('month_right')!== '' ? (int)$request->query->get('month_right'): null;

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $years            = $reader->getYears();
        $dataArray        = $reader->getDataArray();
        $headers          = $reader->getQuestions();

        $yearIndex     = array_search('Year', $headers, true);
        $monthIndex    = array_search('Month', $headers, true);
        $dateIndex     = array_search('Date', $headers, true);
        $tonaliteIndex = array_search('Tonalité', $headers, true);
        $mediaIndex    = array_search('Media', $headers, true);

        /* =======================
         * Résultats panels
         * ======================= */
        $resultsLeft = $resultsRight = [];
        foreach ($questions as $q) {
            $resultsLeft[$q]  = $reader->getQuestionResultFromArray($q, $dataArray, $selectedYearLeft,  $selectedMonthLeft);
            $resultsRight[$q] = $reader->getQuestionResultFromArray($q, $dataArray, $selectedYearRight, $selectedMonthRight);
        }

        /* =======================
         * Totaux mensuels par année
         * ======================= */
        $monthlyTotalsByYear = [];

        foreach ($dataArray as $row) {
            $y = (int)($row[$yearIndex] ?? 0);
            $m = (int)($row[$monthIndex] ?? 0);

            if ($m < 1 || $m > 12) continue;

            $monthlyTotalsByYear[$y] ??= array_fill(1, 12, 0);
            $monthlyTotalsByYear[$y][$m]++;
        }

        /* =======================
         * Timeline tonalité
         * ======================= */
        $tonalitesTimeline = [];
        $tonaliteLabels   = [];

        foreach ($dataArray as $row) {
            $dateRaw  = $row[$dateIndex] ?? null;
            $tonalite = $row[$tonaliteIndex] ?? null;
            if (!$dateRaw || !$tonalite) continue;

            $timestamp = is_numeric($dateRaw)
                ? ($dateRaw - 25569) * 86400
                : strtotime($dateRaw);

            if (!$timestamp) continue;

            $key = date('Y-m', $timestamp);
            $tonalitesTimeline[$key][$tonalite] = ($tonalitesTimeline[$key][$tonalite] ?? 0) + 1;

            if (!in_array($tonalite, $tonaliteLabels, true)) {
                $tonaliteLabels[] = $tonalite;
            }
        }

        ksort($tonalitesTimeline);

        /* =======================
         * Media × Tonalité (par panel)
         * ======================= */
        $buildMediaTonalite = function (?int $year, ?int $month) use (
            $dataArray, $yearIndex, $monthIndex, $mediaIndex, $tonaliteIndex
        ) {
            $counts = [];
            $mediaLabels = [];
            $tonaliteLabels = [];

            foreach ($dataArray as $row) {
                if ($year !== null  && (int)$row[$yearIndex]  !== $year)  continue;
                if ($month !== null && (int)$row[$monthIndex] !== $month) continue;

                $media    = $row[$mediaIndex] ?? null;
                $tonalite = $row[$tonaliteIndex] ?? null;
                if (!$media || !$tonalite) continue;

                $counts[$media][$tonalite] = ($counts[$media][$tonalite] ?? 0) + 1;

                if (!in_array($media, $mediaLabels, true)) {
                    $mediaLabels[] = $media;
                }
                if (!in_array($tonalite, $tonaliteLabels, true)) {
                    $tonaliteLabels[] = $tonalite;
                }
            }

            $datasets = [];
            foreach ($tonaliteLabels as $tonalite) {
                $datasets[] = [
                    'label' => $tonalite,
                    'data'  => array_map(
                        fn($media) => $counts[$media][$tonalite] ?? 0,
                        $mediaLabels
                    )
                ];
            }

            return [$mediaLabels, $datasets];
        };

        [$mediaLabelsLeft,  $mediaTonaliteDatasetsLeft]  = $buildMediaTonalite($selectedYearLeft,  $selectedMonthLeft);
        [$mediaLabelsRight, $mediaTonaliteDatasetsRight] = $buildMediaTonalite($selectedYearRight, $selectedMonthRight);

        /* =======================
         * Render
         * ======================= */
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
            'tonalitesTimeline'  => $tonalitesTimeline,
            'tonaliteLabels'     => $tonaliteLabels,

            /* Media × Tonalité */
            'mediaLabelsLeft'            => $mediaLabelsLeft,
            'mediaTonaliteDatasetsLeft'  => $mediaTonaliteDatasetsLeft,
            'mediaLabelsRight'           => $mediaLabelsRight,
            'mediaTonaliteDatasetsRight' => $mediaTonaliteDatasetsRight,
        ]);
    }
}
