<?php
namespace App\Service;

use App\DTO\QuestionResult;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpKernel\KernelInterface;

class ExcelSurveyReader
{
    private string $filePath;
    private ?\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet = null;

    public function __construct(KernelInterface $kernel)
    {
        $this->filePath = $kernel->getProjectDir() . '/Test.xlsx';
    }

    private function getSheet(): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        if ($this->sheet === null) {
            $reader = new Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->filePath);
            $this->sheet = $spreadsheet->getActiveSheet();
        }
        return $this->sheet;
    }

    public function getQuestions(): array
    {
        $sheet = $this->getSheet();
        $questions = [];
        foreach ($sheet->getRowIterator(1,1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $questions[] = (string)$cell->getValue();
            }
        }
        return $questions;
    }

    public function getYears(): array
    {
        $sheet = $this->getSheet();
        $questions = $this->getQuestions();
        $yearColIndex = array_search('Year', $questions, true);
        if ($yearColIndex === false) return [];

        $yearCol = Coordinate::stringFromColumnIndex($yearColIndex + 1);
        $years = [];
        $totalRows = $sheet->getHighestRow();

        for ($row = 2; $row <= $totalRows; $row++) {
            $y = (int)$sheet->getCell($yearCol . $row)->getValue();
            if ($y) $years[$y] = $y;
        }

        ksort($years);
        return array_values($years);
    }

   public function getQuestionResult(string $question, ?int $year = null, ?int $month = null): QuestionResult
{
    $sheet = $this->getSheet();
    $questions = $this->getQuestions();

    $questionIndex = array_search($question, $questions, true);
    $yearColumnIndex  = array_search('Year', $questions, true);
    $monthColumnIndex = array_search('Month', $questions, true);

    $result = new QuestionResult($question);

    if ($questionIndex === false || $yearColumnIndex === false || $monthColumnIndex === false) {
        return $result;
    }

    $columnIndex = $questionIndex + 1;
    $yearColumn  = Coordinate::stringFromColumnIndex($yearColumnIndex + 1);
    $monthColumn = Coordinate::stringFromColumnIndex($monthColumnIndex + 1);

    $totalRows = $sheet->getHighestRow();
    $counts = [];

    for ($row = 2; $row <= $totalRows; $row++) {

        $rowYear  = (int)$sheet->getCell($yearColumn . $row)->getValue();
        $rowMonth = (int)$sheet->getCell($monthColumn . $row)->getValue();

        if ($year !== null && $rowYear !== $year) {
            continue;
        }

        if ($month !== null && $rowMonth !== $month) {
            continue;
        }

        $column = Coordinate::stringFromColumnIndex($columnIndex);
        $value = $sheet->getCell($column . $row)->getValue();

        if ($value !== null && $value !== '') {
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }
    }

    $total = array_sum($counts);

    foreach ($counts as $label => $count) {
        $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        $result->addModality($label, $count, $percentage);
    }

    return $result;
}

}
