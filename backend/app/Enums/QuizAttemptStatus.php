<?php

namespace App\Enums;

enum QuizAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case NeedsGrading = 'needs_grading';
    case Graded = 'graded';

    public function isFinal(): bool
    {
        return $this !== self::InProgress;
    }
}
