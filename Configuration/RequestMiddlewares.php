<?php

return [
    'frontend' => [
        'webcoast/nawork-uri/set-request-params' => [
            'target' => Nawork\NaworkUri\Middleware\SetRequestParameters::class,
            'after' => [
                'typo3/cms-frontend/page-resolver'
            ],
            'before' => [
                'typo3/cms-frontend/page-argument-validator'
            ]
        ]
    ]
];
