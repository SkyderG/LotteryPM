<?php

namespace lottery\util;

class PercentageCalculator
{
    public static function run(int $initial = 0, int $finish = 0, int $current = 0): int
    {
        $total = $finish - $initial;
        $remains = $finish - $current;

        $to_finish = ($current >= $finish) ? 1 : $remains / $total;
        $runned = 1 - $to_finish;

        return (int) ceil(round($runned * 100));
    }
}