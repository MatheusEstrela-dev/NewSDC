<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

/**
 * Convencao de caminhos do video EAD. Os relativos valem para o disco
 * `treinamentos`; so o log do ffmpeg vive no storage local.
 */
final class CaminhosVideo
{
    public static function partes(string $uploadId): string
    {
        return "uploads/{$uploadId}";
    }

    public static function parte(string $uploadId, int $n): string
    {
        return self::partes($uploadId).'/'.$n.'.part';
    }

    public static function originais(int $videoId): string
    {
        return "originais/{$videoId}";
    }

    public static function original(int $videoId, string $uuid): string
    {
        return self::originais($videoId)."/{$uuid}.mp4";
    }

    public static function hlsRaiz(int $videoId): string
    {
        return "hls/{$videoId}";
    }

    public static function hlsTemporario(int $videoId, string $uuid): string
    {
        return self::hlsRaiz($videoId)."/.tmp-{$uuid}";
    }

    public static function hlsVersao(int $videoId, string $versao): string
    {
        return self::hlsRaiz($videoId).'/'.$versao;
    }

    public static function urlMaster(int $videoId, string $versao): string
    {
        return "/videos/{$videoId}/{$versao}/master.m3u8";
    }

    public static function logFfmpeg(int $videoId, string $versao): string
    {
        return storage_path("logs/ffmpeg/{$videoId}-{$versao}.log");
    }
}
