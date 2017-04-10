<?php

return [
    'naworkuri_clearUrlCache' => [
        'path' => 'tx_naworkuri::clearUrlCache',
        'target' => Nawork\NaworkUri\Cache\ClearCache::class . '::clearUrlCache'
    ],
    'naworkuri_clearConfigurationCache' => [
        'path' => 'tx_naworkuri::clearUrlConfigurationCache',
        'target' => Nawork\NaworkUri\Cache\ClearCache::class . '::clearConfigurationCache'
    ]
];
