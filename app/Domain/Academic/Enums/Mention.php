<?php

namespace App\Domain\Academic\Enums;

/** និទ្ទេស, best first. The key drives the MentionChip colour; labels live in the grading scale. */
enum Mention: string
{
    case Excellent = 'excellent';
    case VeryGood = 'very_good';
    case Good = 'good';
    case FairlyGood = 'fairly_good';
    case Average = 'average';
    case Weak = 'weak';
}
