<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$polly = app(\App\Services\PollyService::class);
$result = $polly->getVoices('ru-RU');

print_r($result);
