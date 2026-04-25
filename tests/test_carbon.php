<?php
require 'vendor/autoload.php';
use Carbon\Carbon;

$durations = ['P1M', 'P3M', 'P1Y', 'P1Y2M'];

foreach ($durations as $duration) {
    try {
        $date = Carbon::now()->sub($duration);
        echo "$duration => " . $date->format('Y/m/d') . "\n";
    } catch (\Exception $e) {
        echo "$duration => Error: " . $e->getMessage() . "\n";
    }
}
