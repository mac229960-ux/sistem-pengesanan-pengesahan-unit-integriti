<?php

return [
    'sla_bulan' => (int) env('INTEGRITI_SLA_BULAN', 6),
    'retensi_tahun' => (int) env('INTEGRITI_RETENSI_TAHUN', 7),

    // Peringatan SLA dihantar apabila baki hari mencapai nilai berikut.
    'peringatan_baki_hari' => [90, 30, 7],
    // Selepas lewat, peringatan diulang setiap N hari.
    'peringatan_lewat_selang_hari' => 7,

    'otp' => [
        'minit' => (int) env('INTEGRITI_OTP_MINIT', 5),
        'cubaan_maks' => (int) env('INTEGRITI_OTP_CUBAAN_MAKS', 5),
        'papar_dev' => (bool) env('INTEGRITI_OTP_PAPAR_DEV', false),
        // Jika ditetapkan, semua OTP dihantar ke e-mel ini (fasa UAT). Kosongkan untuk produksi.
        'emel_tetap' => env('INTEGRITI_OTP_EMEL_TETAP'),
    ],

    'bukti' => [
        'saiz_maks_kb' => 20480,
        'mimes' => 'pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,wav,m4a,mp4,mov,zip,msg,eml',
    ],

    'ai' => [
        'provider' => env('AI_PROVIDER', 'mock'),
        'anonimkan' => (bool) env('AI_ANONIMKAN', true),
        'timeout' => (int) env('AI_TIMEOUT', 180),
        'bilangan_rujukan' => 8,
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
            'effort' => env('ANTHROPIC_EFFORT', 'high'),
            'max_tokens' => 16000,
        ],
        'ollama' => [
            'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3.1:8b'),
        ],
    ],

    'hr' => [
        'driver' => env('HR_DRIVER', 'mock'),
        'api_url' => env('HR_API_URL'),
        'api_token' => env('HR_API_TOKEN'),
    ],
];
