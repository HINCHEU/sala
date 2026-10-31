<?php

namespace App\Domain\Results\Enums;

enum DocumentKind: string
{
    case ScoreSheet = 'score_sheet';
    case ReportCards = 'report_cards';
    case HonorRoll = 'honor_roll';
}
