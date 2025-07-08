<?php

declare(strict_types=1);

namespace Doudian\Utils;

class SignUtil
{
    public static function sign(string $appKey, string $appSecret, string $method, int $timestamp, string $paramJson): string
    {
        $paramPattern = sprintf('app_key%smethod%sparam_json%stimestamp%sv2', $appKey, $method, $paramJson, $timestamp);
        $signPattern = $appSecret . $paramPattern . $appSecret;
        
        return hash_hmac('sha256', $signPattern, $appSecret);
    }

    public static function spiSign(string $appKey, string $appSecret, int $timestamp, string $paramJson, int $signMethod): string
    {
        $paramPattern = sprintf('app_key%sparam_json%stimestamp%s', $appKey, $paramJson, $timestamp);
        $signPattern = $appSecret . $paramPattern . $appSecret;
        
        if ($signMethod === 2) {
            return hash_hmac('sha256', $signPattern, $appSecret);
        }
        
        return md5($signPattern);
    }

    public static function marshal(?object $param): string
    {
        if ($param === null) {
            return '{}';
        }
        
        $arr = self::objToArray($param);
        self::recKSort($arr);
        
        return json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected static function recKSort(array &$arr): void
    {
        $kstring = true;
        foreach ($arr as $k => &$v) {
            if (!is_string($k)) {
                $kstring = false;
            }
            if (is_array($v)) {
                self::recKSort($v);
            }
        }
        if ($kstring) {
            ksort($arr);
        }
    }

    protected static function objToArray(object $obj): array
    {
        $jsonStr = json_encode($obj);
        return json_decode($jsonStr, true) ?: [];
    }
} 