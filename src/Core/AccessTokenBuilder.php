<?php

declare(strict_types=1);

namespace Doudian\Core;

use Doudian\Api\Token\CreateTokenRequest;
use Doudian\Api\Token\Param\CreateTokenParam;
use Doudian\Api\Token\RefreshTokenRequest;
use Doudian\Api\Token\Param\RefreshTokenParam;

class AccessTokenBuilder
{
    public const ACCESS_TOKEN_CODE = 1;
    public const ACCESS_TOKEN_SHOP_ID = 2;

    public static function build(string $codeOrShopId, int $type = self::ACCESS_TOKEN_CODE, string $clientName = 'default'): AccessToken
    {
        $request = new CreateTokenRequest();
        $param = new CreateTokenParam();
        
        if ($type === self::ACCESS_TOKEN_SHOP_ID) {
            $param->shop_id = $codeOrShopId;
            $param->grant_type = 'authorization_self';
            $param->code = '';
        } elseif ($type === self::ACCESS_TOKEN_CODE) {
            $param->grant_type = 'authorization_code';
            $param->code = $codeOrShopId;
        }
        
        $request->setParam($param);
        $resp = $request->execute(null, $clientName);
        
        return AccessToken::wrap($resp);
    }

    public static function refresh($token, string $clientName = 'default'): AccessToken
    {
        $request = new RefreshTokenRequest();
        $param = new RefreshTokenParam();
        $param->grant_type = 'refresh_token';
        
        if (is_string($token)) {
            $param->refresh_token = $token;
        } else {
            $param->refresh_token = $token->getRefreshToken();
        }
        
        $request->setParam($param);
        $resp = $request->execute(null, $clientName);
        
        return AccessToken::wrap($resp);
    }

    public static function parse(string $accessTokenStr): AccessToken
    {
        $tokenData = (object) ['access_token' => $accessTokenStr];
        $accessToken = new AccessToken();
        $accessToken->setData($tokenData);
        
        return $accessToken;
    }
} 