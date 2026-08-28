<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'wechat' => [
        'app_id' => env('WECHAT_APP_ID'),
        'secret' => env('WECHAT_SECRET')
    ],

    'graphical' => [
        'app_id' => env('GRAPHICAL_APP_ID'),
        'secret_key' => env('GRAPHICAL_App_Secret_Key')
    ],

    'tencent' => [
        'secret_id' => env('TENCENT_SECRET_ID'),
        'secret_key' => env('TENCENT_SECRET_KEY')
    ],

    'amap' => [
        'key' => env('AMAP_KEY'),
    ],

    'xhhai' => [
        'base_url' => env('XHHAI_BASE_URL', 'https://api.xhhai.top/v1'),
        'api_key' => env('XHHAI_API_KEY'),
        'image_model' => env('XHHAI_IMAGE_MODEL', 'gpt-image-2'),
        'chat_model' => env('XHHAI_CHAT_MODEL', 'gpt-4o-mini'),
        'image_model_map' => [
            'seedream-5.0-lite' => env('XHHAI_IMAGE_MODEL', 'gpt-image-2'),
            'seedream-4.4' => env('XHHAI_IMAGE_MODEL_FAST', env('XHHAI_IMAGE_MODEL', 'gpt-image-2')),
        ],
        'verify_ssl' => filter_var(env('XHHAI_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    // OpenAI 兼容图片备用中转站：参与生图兜底链路（XHHAI -> SUB2API -> Seedream）。
    'sub2api' => [
        'base_url' => env('SUB2API_BASE_URL', 'http://43.131.227.14:8080/v1'),
        'api_key' => env('SUB2API_API_KEY'),
        'image_model' => env('SUB2API_IMAGE_MODEL', env('XHHAI_IMAGE_MODEL', 'gpt-image-2')),
        'chat_base_url' => env('SUB2API_CHAT_BASE_URL', env('SUB2API_BASE_URL', 'http://43.131.227.14:8080/v1')),
        'chat_api_key' => env('SUB2API_CHAT_API_KEY', env('SUB2API_API_KEY')),
        'chat_model' => env('SUB2API_CHAT_MODEL', 'gpt-5.6-terra'),
        'image_model_map' => [
            'seedream-5.0-lite' => env('SUB2API_IMAGE_MODEL', env('XHHAI_IMAGE_MODEL', 'gpt-image-2')),
            'seedream-4.4' => env('SUB2API_IMAGE_MODEL_FAST', env('SUB2API_IMAGE_MODEL', env('XHHAI_IMAGE_MODEL_FAST', env('XHHAI_IMAGE_MODEL', 'gpt-image-2')))),
        ],
        'verify_ssl' => filter_var(env('SUB2API_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    ],

    'seedream' => [
        'base_url' => env('SEEDREAM_BASE_URL', 'https://ark.cn-beijing.volces.com/api/v3'),
        'api_key' => env('SEEDREAM_API_KEY'),
        'default_model' => env('SEEDREAM_DEFAULT_MODEL', 'doubao-seedream-5-0-260128'),
        // 详情文案/深度思考等对话模型（不能用 doubao-seedream-* 生图模型）
        // 默认与 intent_model 对齐：旧默认 doubao-1-5-pro-32k-250115 在多数账号未开通会 404
        'chat_model' => env('SEEDREAM_CHAT_MODEL', env('SEEDREAM_INTENT_MODEL', 'doubao-seed-2-0-lite-260428')),
        // 活动自由文本意图理解（方舟 /responses）
        'intent_model' => env('SEEDREAM_INTENT_MODEL', 'doubao-seed-2-0-lite-260428'),
        'watermark' => filter_var(env('SEEDREAM_WATERMARK', false), FILTER_VALIDATE_BOOL),
        'image_model_map' => [
            'seedream-5.0-lite' => env('SEEDREAM_DEFAULT_MODEL', 'doubao-seedream-5-0-260128'),
            'seedream-4.4' => env('SEEDREAM_FAST_MODEL', 'doubao-seedream-4-0-250828'),
            'gpt-image-2' => env('SEEDREAM_DEFAULT_MODEL', 'doubao-seedream-5-0-260128'),
        ],
    ],

    // 活动详情文案等：sub2api=优先中转站；可设 seedream / xhhai / auto 随时切换。
    'ai_chat_text' => [
        'provider' => env('AI_CHAT_TEXT_PROVIDER', 'auto'),
    ],

    'ai_upstream' => [
        'base_url' => env('AI_UPSTREAM_API_BASE_URL', 'http://klapis.liebiankuai.com'),
        'timeout' => (int) env('AI_UPSTREAM_TIMEOUT', 310),
    ],
];
