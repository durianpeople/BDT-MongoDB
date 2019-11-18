<?php

namespace BDT\Controller;

class Converter
{
    public static function cmToFeet(?float $cm, bool $force_inches = false): string
    {
        if ($cm == null) return "";
        $inches_f = $cm / 2.54;
        $feet = intval($inches_f / 12);
        $inches = $inches_f % 12;
        if (!$force_inches) return sprintf("%d' %d\"", $feet, $inches);
        return sprintf("%d\"", $inches_f);
    }

    public static function kgToLb(?float $kg)
    {
        if ($kg == null) return "";
        return floor($kg * 2.20462) . " lbs.";
    }

    public static function europeDateToAmerican(?string $date)
    {
        if ($date == null) return "";
        return date("M d, Y", strtotime($date));
    }
}
