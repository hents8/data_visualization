<?php
namespace App\DTO;

class QuestionResult
{
    private string $question;
    private int $totalResponses;
    private array $modalities = [];

    public function __construct(string $question)
    {
        $this->question = $question;
        $this->totalResponses = 0;
    }

    public function addModality(string $label, int $count): void
    {
        $percentage = 0;
        if ($this->totalResponses + $count > 0) {
            $percentage = round(($count / ($this->totalResponses + $count)) * 100, 2);
        }

        $this->modalities[] = [
            'label' => $label,
            'count' => $count,
            'percentage' => $percentage
        ];

        $this->totalResponses += $count;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getModalities(): array
    {
        return $this->modalities;
    }

    public function getLabels(): array
    {
        return array_column($this->modalities, 'label');
    }

    public function getFrequencies(): array
    {
        return array_column($this->modalities, 'count');
    }

    public function getTotalResponses(): int
    {
        return $this->totalResponses;
    }
}
