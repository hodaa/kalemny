<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Providers
    |--------------------------------------------------------------------------
    |
    | The default provider is used to generate the media join payload when a
    | call is accepted. Each provider wraps a distinct realtime-media vendor
    | (Agora, LiveKit, Daily, ...) behind the same contract so a new provider
    | can be swapped in through configuration alone.
    |
    */

    'media' => [
        'default' => env('CALL_MEDIA_PROVIDER', 'agora'),

        'providers' => [
            'agora' => [
                'app_id' => env('AGORA_APP_ID'),
                'app_certificate' => env('AGORA_CERTIFICATE'),
                'token_expires_after' => (int) env('AGORA_TOKEN_EXPIRES_AFTER', 3600),
            ],
        ],
    ],
];
