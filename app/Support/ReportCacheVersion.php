<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ReportCacheVersion
{
    public const ORDERS_SUMMARY_VERSION_KEY = 'report:orders:summary:version';

    public static function currentOrdersSummaryVersion(): int
    {
        return (int) Cache::get(self::ORDERS_SUMMARY_VERSION_KEY, 1);
    }

    public static function bumpOrdersSummaryVersion(): void
    {
        if (! Cache::has(self::ORDERS_SUMMARY_VERSION_KEY)) {
            Cache::forever(self::ORDERS_SUMMARY_VERSION_KEY, 1);
        }

        Cache::increment(self::ORDERS_SUMMARY_VERSION_KEY);
    }
}
