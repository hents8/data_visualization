<?php
namespace App\Service;

use App\DTO\QuestionResult;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpKernel\KernelInterface;

class ExcelSurveyReader
{
    private string $filePath;

    // On stocke la feuille pour ne pas recharger le fichier plusieurs fois
    private ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet = null;

    public function __construct(KernelInterface $kernel)
    {
        $this->filePath = $kernel->getProjectDir() . '/Test.xlsx';
    }

    private function getSheet(): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        if ($this->sheet === null) {
            $reader = new Xlsx();
            $reader->setReadDataOnly(true); // optimisation mémoire
            $spreadsheet = $reader->load($this->filePath);
            $this->sheet = $spreadsheet->getActiveSheet();
        }

        return $this->sheet;
    }

    public function getQuestions(): array
    {
        $sheet = $this->getSheet();
        $questions = [];

        // Lecture de la première ligne uniquement
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $questions[] = (string) $cell->getValue();
            }
        }

        return $questions;
    }

    public function getQuestionResult(string $question): QuestionResult
    {
        $sheet = $this->getSheet();
        $questions = $this->getQuestions();

        $questionIndex = array_search($question, $questions, true);
        $result = new QuestionResult($question);

        if ($questionIndex === false) {
            return $result;
        }

        $columnIndex = $questionIndex + 1;
        $totalRows = $sheet->getHighestRow();
        $counts = [];

        for ($row = 2; $row <= $totalRows; $row++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $value = $sheet->getCell($column . $row)->getValue();

            if ($value !== null && $value !== '') {
                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }
        }

        $total = array_sum($counts); // total réponses

        foreach ($counts as $label => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $result->addModality($label, $count, $percentage);
        }

        return $result;
    }
}
