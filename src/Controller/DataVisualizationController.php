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
        // Questions fixes pour chaque panel
        $questionsLeft  = ['Media', 'Medium', 'Secteur', 'Citée', 'Tonalité'];
        $questionsRight = ['Media', 'Medium', 'Secteur', 'Citée', 'Tonalité'];

        // Récupération filtres GET séparés pour chaque panel
        $yearLeftRaw  = $request->query->get('year_left');
        $monthLeftRaw = $request->query->get('month_left');

        $yearRightRaw  = $request->query->get('year_right');
        $monthRightRaw = $request->query->get('month_right');

        $selectedYearLeft  = ($yearLeftRaw !== null && $yearLeftRaw !== '') ? (int)$yearLeftRaw : null;
        $selectedMonthLeft = ($monthLeftRaw !== null && $monthLeftRaw !== '') ? (int)$monthLeftRaw : null;

        $selectedYearRight  = ($yearRightRaw !== null && $yearRightRaw !== '') ? (int)$yearRightRaw : null;
        $selectedMonthRight = ($monthRightRaw !== null && $monthRightRaw !== '') ? (int)$monthRightRaw : null;

        // Mapping mois en nom
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        // Résultats panel gauche
        $resultsLeft = [];
        foreach ($questionsLeft as $q) {
            $resultsLeft[$q] = $reader->getQuestionResult($q, $selectedYearLeft, $selectedMonthLeft);
        }

        // Résultats panel droit
        $resultsRight = [];
        foreach ($questionsRight as $q) {
            $resultsRight[$q] = $reader->getQuestionResult($q, $selectedYearRight, $selectedMonthRight);
        }

        return $this->render('dashboard/index.html.twig', [
            'resultsLeft'        => $resultsLeft,
            'resultsRight'       => $resultsRight,
            'months'             => $months,
            'years'              => $reader->getYears(),
            'selectedYearLeft'   => $selectedYearLeft,
            'selectedMonthLeft'  => $selectedMonthLeft,
            'selectedYearRight'  => $selectedYearRight,
            'selectedMonthRight' => $selectedMonthRight,
        ]);
    }
}
