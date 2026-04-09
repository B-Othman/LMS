<?php

namespace App\Enums;

enum QuizQuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case MultiSelect = 'multi_select';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';

    public function isChoiceBased(): bool
    {
        return match ($this) {
            self::MultipleChoice, self::MultiSelect, self::TrueFalse => true,
            self::ShortAnswer => false,
        };
    }

    public function supportsMultipleSelections(): bool
    {
        return $this === self::MultiSelect;
    }
}
