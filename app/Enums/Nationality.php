<?php

namespace App\Enums;

enum Nationality: string
{
    case SAUDI = 'سعودي';
    case EGYPTIAN = 'مصري';
    case SUDANESE = 'سوداني';
    case YEMENI = 'يمني';
    case JORDANIAN = 'أردني';
    case SYRIAN = 'سوري';
    case PALESTINIAN = 'فلسطيني';
    case PAKISTANI = 'باكستاني';
    case INDIAN = 'هندي';
    case BANGLADESHI = 'بنغلاديشي';
    case OTHER = 'أخرى';

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->value])
            ->toArray();
    }
}
