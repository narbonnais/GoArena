<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KataGo Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the KataGo executable binary.
    |
    */
    'binary' => env('KATAGO_BINARY', '/usr/local/bin/katago'),

    /*
    |--------------------------------------------------------------------------
    | KataGo Model Path
    |--------------------------------------------------------------------------
    |
    | The path to the KataGo neural network model file.
    | The Human SL model is recommended for natural play.
    |
    */
    'model' => env('KATAGO_MODEL', storage_path('katago/b18c384nbt-humanv0.bin.gz')),

    /*
    |--------------------------------------------------------------------------
    | KataGo Config Path
    |--------------------------------------------------------------------------
    |
    | The path to the KataGo GTP configuration file.
    |
    */
    'config' => env('KATAGO_CONFIG', storage_path('katago/gtp.cfg')),

    /*
    |--------------------------------------------------------------------------
    | Game Profile
    |--------------------------------------------------------------------------
    |
    | Profile used for AI moves during gameplay.
    | Uses full strength (no humanSLProfile restriction) with fast thinking.
    |
    */
    'game' => [
        'humanSLProfile' => null,        // Full strength, no restriction
        'maxVisits' => 300,              // Fast, strong play
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis Profile
    |--------------------------------------------------------------------------
    |
    | Profile for the evaluator in the analysis tab.
    | Uses no humanSLProfile restriction for full-strength analysis.
    |
    */
    'analysis' => [
        'humanSLProfile' => null,        // No restriction
        'maxVisits' => 500,              // Deep analysis
        'limits' => [
            'maxVisits' => ['min' => 100, 'max' => 5000, 'default' => 500],
            'numSearchThreads' => ['min' => 1, 'max' => 8, 'default' => 2],
            'multiPV' => ['min' => 1, 'max' => 10, 'default' => 5],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Process Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for KataGo responses.
    |
    */
    'timeout' => 30,
];
