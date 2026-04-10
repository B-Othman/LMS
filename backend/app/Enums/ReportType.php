<?php

namespace App\Enums;

enum ReportType: string
{
    case Completions = 'completions';
    case LearnerProgress = 'learner_progress';
    case Assessments = 'assessments';
    case CourseDetail = 'course_detail';

    public function label(): string
    {
        return match ($this) {
            self::Completions => 'Course Completions',
            self::LearnerProgress => 'Learner Progress',
            self::Assessments => 'Assessment Analytics',
            self::CourseDetail => 'Course Detail',
        };
    }
}
