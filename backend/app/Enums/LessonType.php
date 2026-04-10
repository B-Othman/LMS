<?php

namespace App\Enums;

enum LessonType: string
{
    case Video = 'video';
    case Document = 'document';
    case Text = 'text';
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Scorm = 'scorm';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Document => 'Document',
            self::Text => 'Text',
            self::Quiz => 'Quiz',
            self::Assignment => 'Assignment',
            self::Scorm => 'SCORM',
        };
    }
}
