<?php

return [
    'default_chat_id' => env('T_DUMP_CHAT_ID'),
    'default_token' => env('T_DUMP_TOKEN'),

    'dev_chat_id' => env('T_DUMP_DEV1_CHAT_ID'),
    'dev_token' => env('T_DUMP_DEV1_TOKEN'),

    'prod_chat_id' => env('T_DUMP_CHAT_ID_PROD'),
    'prod_token' => env('T_DUMP_TOKEN_PROD'),

    'enabled' => env('T_DUMP_ENABLED', true),
    'timeout' => env('T_DUMP_TIMEOUT', 10),
    'max_message_length' => env('T_DUMP_MAX_LENGTH', 4096),
];
