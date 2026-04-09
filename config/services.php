<?php

$vibePollEnv = env('VIBE_POLL_TIMEOUT_SECONDS');
$vibePollSeconds = ($vibePollEnv === null || $vibePollEnv === '')
    ? 180
    : (((int) $vibePollEnv >= 30) ? (int) $vibePollEnv : 180);

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'discogs' => [
        'username' => env('DISCOGS_USERNAME', ''),
        'token' => env('DISCOGS_TOKEN', ''),
    ],

    'huggingface' => [
        'token' => env('HUGGINGFACE_API_TOKEN', ''),
    ],

    'vibe' => [
        'poll_timeout_seconds' => $vibePollSeconds,
    ],

];
