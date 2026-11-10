<?php

declare(strict_types=1);

return [
    'video' => [
        'disco' => 'treinamentos',
        'tamanho_parte_bytes' => (int) env('TREINAMENTO_VIDEO_PARTE_BYTES', 8388608),
        'tamanho_maximo_bytes' => (int) env('TREINAMENTO_VIDEO_MAX_BYTES', 2147483648),
        'mimes' => ['video/mp4', 'video/quicktime', 'video/x-matroska'],
        'upload_expira_horas' => 24,
        'manter_original' => (bool) env('TREINAMENTO_VIDEO_MANTER_ORIGINAL', false),
        'conexao_fila' => 'redis-videos',
        'fila' => 'videos',
        'auth_cache_segundos' => 300,
        'servir_pelo_app' => (bool) env('TREINAMENTO_VIDEO_SERVIR_PELO_APP', false),
        'log_ffmpeg_dias' => 30,
        'ffmpeg' => [
            'binario' => env('FFMPEG_BIN', 'ffmpeg'),
            'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
            'nice' => 10,
            'threads' => 2,
            'preset' => 'veryfast',
            'crf' => 23,
            'hls_time' => 6,
            'audio_bitrate' => '128k',
        ],
        'renditions' => [
            '480p' => ['largura' => 854, 'altura' => 480, 'bitrate' => '800k', 'ativa' => true],
            '720p' => ['largura' => 1280, 'altura' => 720, 'bitrate' => '2500k', 'ativa' => true],
            '1080p' => ['largura' => 1920, 'altura' => 1080, 'bitrate' => '5000k', 'ativa' => (bool) env('TREINAMENTO_VIDEO_1080P', false)],
        ],
    ],
];
