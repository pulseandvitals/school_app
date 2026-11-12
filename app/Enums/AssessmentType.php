<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Quiz = 'quiz';
    case Examination = 'examination';
    case Assignment = 'assignment';
    case Activity = 'activity';
    case Custom = 'custom';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
