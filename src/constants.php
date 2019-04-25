<?php

define('DATA_TABLE', 'cb_data');

call_user_func(function () {
    $min = 60;
    $hour = 60 * $min;
    $day = 24 * $hour;
    $toUtc = -3 * $hour;

    foreach ([
                 'MIN' => $min,
                 'HOUR' => $hour,
                 'DAY' => $day,
                 'TO_UTC' => $toUtc,
                 'WORK_START' => 10 * $hour + $toUtc,
                 'LUNCH_START' => 13 * $hour + $toUtc,
                 'LUNCH_FINISH' => 13 * $hour + 30 * $min + $toUtc,
                 'WORK_FINISH' => 18 * $hour + 30 * $min + $toUtc,
             ] as $key => $val) {
        define($key, $val);
    }
});
