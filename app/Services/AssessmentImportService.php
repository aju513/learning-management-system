<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AssessmentImportService
{
    public function __construct(private readonly AssessmentRepositoryInterface $assessments) {}

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions');
        $headers = ['type', 'question', 'marks', 'option_1', 'option_2', 'option_3', 'option_4', 'option_5', 'option_6', 'option_7', 'option_8', 'correct_options', 'reference_answer'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            ['single_choice', 'Which number is even?', 1, '1', '2', '3', '5', '', '', '', '', '2', ''],
            ['multiple_choice', 'Select the primary colors.', 2, 'Red', 'Blue', 'Green', 'Yellow', '', '', '', '', '1,2,4', ''],
            ['yes_no', 'The earth orbits the sun.', 1, 'Yes', 'No', '', '', '', '', '', '', 'Yes', ''],
            ['question_answer', 'Explain the purpose of validation.', 5, '', '', '', '', '', '', '', '', '', 'Validation rejects invalid input before it reaches business logic.'],
        ], null, 'A2');
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function import(Assessment $assessment, UploadedFile $file, User $actor): int
    {
        if ($this->assessments->hasAttempts($assessment)) {
            throw ValidationException::withMessages(['file' => 'Questions cannot change after attempts have started.']);
        }

        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        try {
            $rows = $reader->load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'The uploaded workbook could not be read.']);
        }

        $headers = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows) ?? []);
        $requiredHeaders = ['type', 'question', 'marks', 'correct_options', 'reference_answer'];
        if (array_diff($requiredHeaders, $headers)) {
            throw ValidationException::withMessages(['file' => 'Use the downloadable template without renaming its columns.']);
        }

        $parsed = [];
        $errors = [];
        foreach ($rows as $offset => $values) {
            $rowNumber = $offset + 2;
            $row = array_combine($headers, array_pad($values, count($headers), null));
            if (! collect($row)->contains(fn ($value) => filled($value))) {
                continue;
            }
            $question = $this->parseRow($row, $rowNumber, $errors);
            if ($question) {
                $parsed[] = $question;
            }
        }

        if ($parsed === [] && $errors === []) {
            $errors[] = 'The workbook does not contain any question rows.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        DB::transaction(function () use ($assessment, $parsed): void {
            $position = $this->assessments->nextQuestionPosition($assessment);
            foreach ($parsed as $data) {
                $options = $data['options'];
                unset($data['options']);
                $question = $this->assessments->createQuestion([
                    ...$data,
                    'assessment_id' => $assessment->id,
                    'position' => $position++,
                ]);
                $this->assessments->replaceOptions($question, $options);
            }
        });

        activity('lms')->causedBy($actor)->performedOn($assessment)->event('assessment-questions.imported')
            ->withProperties(['count' => count($parsed)])->log('Assessment questions imported');

        return count($parsed);
    }

    private function parseRow(array $row, int $number, array &$errors): ?array
    {
        $type = match (strtolower(trim((string) ($row['type'] ?? '')))) {
            'single_choice', 'multiple_choice_one' => QuestionType::SingleChoice,
            'multiple_choice', 'multiple_select' => QuestionType::MultipleChoice,
            'yes_no', 'true_false' => QuestionType::TrueFalse,
            'question_answer', 'q&a' => QuestionType::QuestionAnswer,
            default => null,
        };
        $prompt = trim((string) ($row['question'] ?? ''));
        $marks = filter_var($row['marks'] ?? null, FILTER_VALIDATE_FLOAT);
        if (! $type) {
            $errors[] = "Row {$number}: type is not supported.";
        }
        if ($prompt === '' || mb_strlen($prompt) > 5000) {
            $errors[] = "Row {$number}: question is required and must not exceed 5000 characters.";
        }
        if ($marks === false || $marks <= 0 || $marks > 10000) {
            $errors[] = "Row {$number}: marks must be between 0.01 and 10000.";
        }
        if (! $type || $prompt === '' || $marks === false) {
            return null;
        }

        if ($type === QuestionType::QuestionAnswer) {
            $reference = trim((string) ($row['reference_answer'] ?? ''));
            if ($reference === '') {
                $errors[] = "Row {$number}: reference_answer is required for question_answer.";
            }

            return ['prompt' => $prompt, 'type' => $type, 'marks' => $marks, 'reference_answer' => $reference, 'options' => []];
        }

        $optionTexts = [];
        foreach (range(1, 8) as $index) {
            $text = trim((string) ($row["option_{$index}"] ?? ''));
            if ($text !== '') {
                $optionTexts[] = $text;
            }
        }
        if ($type === QuestionType::TrueFalse) {
            $optionTexts = ['Yes', 'No'];
            $answer = strtolower(trim((string) ($row['correct_options'] ?? '')));
            $correctIndexes = $answer === 'yes' ? [0] : ($answer === 'no' ? [1] : []);
        } else {
            $correctIndexes = collect(explode(',', (string) ($row['correct_options'] ?? '')))
                ->map(fn ($value) => (int) trim($value) - 1)->filter(fn ($value) => $value >= 0)->unique()->values()->all();
        }
        if (count($optionTexts) < 2 || count($optionTexts) > 8 || count(array_unique($optionTexts)) !== count($optionTexts)) {
            $errors[] = "Row {$number}: provide 2 to 8 distinct options.";
        }
        if ($correctIndexes === [] || collect($correctIndexes)->contains(fn ($index) => ! array_key_exists($index, $optionTexts))) {
            $errors[] = "Row {$number}: correct_options must identify valid options.";
        }
        if ($type !== QuestionType::MultipleChoice && count($correctIndexes) !== 1) {
            $errors[] = "Row {$number}: this type requires exactly one correct option.";
        }

        return [
            'prompt' => $prompt,
            'type' => $type,
            'marks' => $marks,
            'reference_answer' => null,
            'options' => collect($optionTexts)->values()->map(fn ($text, $index) => [
                'option_text' => $text,
                'is_correct' => in_array($index, $correctIndexes, true),
                'position' => $index + 1,
            ])->all(),
        ];
    }
}
