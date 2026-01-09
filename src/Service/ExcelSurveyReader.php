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
    private ?array $dataCache = null; // Stockage du tableau complet pour éviter plusieurs lectures

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
        foreach ($sheet->getRowIterator(1, 1) as $row) {
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

    /**
     * Récupère le fichier Excel sous forme de tableau pour réutilisation multiple
     */
public function getDataArray(): array
{
    if ($this->dataCache !== null) {
        return $this->dataCache;
    }

    $sheet = $this->getSheet();
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    $data = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $row;
            // Index 0-based pour correspondre à getQuestions()
            $rowData[$col - 1] = $sheet->getCell($cellCoordinate)->getValue();
        }
        $data[] = $rowData;
    }

    $this->dataCache = $data;
    return $data;
}



    /**
     * Calcule le résultat d'une question depuis un tableau Excel
     */
    public function getQuestionResultFromArray(string $question, array $data, ?int $year = null, ?int $month = null): QuestionResult
    {
        $questions = $this->getQuestions();

        $questionIndex = array_search($question, $questions, true);
        $yearIndex     = array_search('Year', $questions, true);
        $monthIndex    = array_search('Month', $questions, true);

        $result = new QuestionResult($question);
        if ($questionIndex === false || $yearIndex === false || $monthIndex === false) {
            return $result;
        }

        $counts = [];
        foreach ($data as $row) {
            $rowYear  = (int)($row[$yearIndex] ?? 0);
            $rowMonth = (int)($row[$monthIndex] ?? 0);

            if ($year !== null && $rowYear !== $year) continue;
            if ($month !== null && $rowMonth !== $month) continue;

            $value = $row[$questionIndex] ?? null;
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

    /**
     * Ancienne méthode conservée pour compatibilité (lit directement depuis Excel)
     */
    public function getQuestionResult(string $question, ?int $year = null, ?int $month = null): QuestionResult
    {
        $data = $this->getDataArray();
        return $this->getQuestionResultFromArray($question, $data, $year, $month);
    }
}
