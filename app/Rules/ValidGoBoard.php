<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidGoBoard implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * The field name containing the board size.
     */
    protected string $sizeField;

    public function __construct(string $sizeField = 'boardSize')
    {
        $this->sizeField = $sizeField;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The :attribute must be an array.');

            return;
        }

        $size = $this->data[$this->sizeField] ?? $this->data['size'] ?? null;

        if ($size === null) {
            $fail('Board size is required to validate the board.');

            return;
        }

        $size = (int) $size;

        // Check board has correct number of rows
        if (count($value) !== $size) {
            $fail("The :attribute must have exactly {$size} rows.");

            return;
        }

        // Check each row
        foreach ($value as $rowIndex => $row) {
            if (! is_array($row)) {
                $fail("Row {$rowIndex} in :attribute must be an array.");

                return;
            }

            if (count($row) !== $size) {
                $fail("Row {$rowIndex} in :attribute must have exactly {$size} columns.");

                return;
            }

            // Check each cell contains valid value
            foreach ($row as $colIndex => $cell) {
                if ($cell !== null && $cell !== 'black' && $cell !== 'white') {
                    $fail("Cell [{$rowIndex}][{$colIndex}] in :attribute must be null, 'black', or 'white'.");

                    return;
                }
            }
        }
    }
}
