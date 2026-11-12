<?php

namespace App\Enums;

enum GrowthChangeReason: string
{
    case ScoreRecorded = 'score_recorded';
    case ScoreUpdated = 'score_updated';
    case ScoreRemoved = 'score_removed';
    case RulesChanged = 'rules_changed';
}
