<?php

namespace App\Enums;

/**
 * Activities students choose for themselves outside their major.
 * TalentDay = interest classes (cooking, English, programming, ...), Ekskul = sports, religious clubs and scouting.
 */
enum ActivityCategory: string
{
    case TalentDay = 'talent_day';
    case Ekskul = 'ekskul';
}
