<?php
namespace App\Controller;

use App\Service\ExcelSurveyReader;
use App\DTO\QuestionResult;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DataVisualizationController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, ExcelSurveyReader $reader)
    {
        $questions = $reader->getQuestions();
        $selectedQuestion = $request->query->get('question');

        $result = null;

        if ($selectedQuestion) {
            $result = $reader->getQuestionResult($selectedQuestion);
        }

        return $this->render('dashboard/index.html.twig', [
            'questions' => $questions,
            'selectedQuestion' => $selectedQuestion,
            'result' => $result, // DTO
        ]);
    }
}
