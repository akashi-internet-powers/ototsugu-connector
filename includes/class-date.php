<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 開催日の表示整形。書式文字列は翻訳可能にし、月名・曜日名は wp_date() でサイトのロケールに従って取得する。
 */
class OTSG_Date
{
    public static function parse(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value, wp_timezone());

        return $date ?: null;
    }

    public static function format(DateTimeImmutable $date, string $format): string
    {
        return (string) wp_date($format, $date->getTimestamp(), $date->getTimezone());
    }

    /**
     * 年月日(例: September 6, 2026)。
     */
    public static function full(DateTimeImmutable $date): string
    {
        return self::format(
            $date,
            /* translators: Date format (PHP date() syntax) for an event date, e.g. "F j, Y" for September 6, 2026. */
            _x('F j, Y', 'event date format', 'ototsugu-connector')
        );
    }

    /**
     * 一覧ブロックの日付表示。$style は full / date / slash / short のいずれか。曜日を付ける。
     */
    public static function with_weekday(DateTimeImmutable $date, string $style): string
    {
        switch ($style) {
            case 'slash':
                $formatted = self::format($date, 'Y/m/d');
                break;
            case 'short':
                $formatted = self::format(
                    $date,
                    /* translators: Date format (PHP date() syntax) for an event date without the year, e.g. "M j" for Sep 6. */
                    _x('M j', 'event date format without year', 'ototsugu-connector')
                );
                break;
            case 'full':
            case 'date':
            default:
                $formatted = self::full($date);
                break;
        }

        return sprintf(
            /* translators: 1: Formatted event date (e.g. September 6, 2026), 2: Abbreviated weekday name (e.g. Sun). */
            _x('%1$s (%2$s)', 'event date followed by weekday', 'ototsugu-connector'),
            $formatted,
            self::format($date, 'D')
        );
    }
}
