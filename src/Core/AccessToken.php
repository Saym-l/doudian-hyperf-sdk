<?php

declare(strict_types=1);

namespace Doudian\Core;

class AccessToken
{
    protected ?int $code = null;
    protected ?string $msg = null;
    protected ?string $subCode = null;
    protected ?string $subMsg = null;
    protected ?string $logId = null;
    protected ?object $data = null;

    public static function wrap(object $resp): self
    {
        $accessToken = new self();
        
        if (property_exists($resp, 'code')) {
            $accessToken->setCode($resp->code);
        }
        if (property_exists($resp, 'msg')) {
            $accessToken->setMsg($resp->msg);
        }
        if (property_exists($resp, 'sub_code')) {
            $accessToken->setSubCode($resp->sub_code);
        }
        if (property_exists($resp, 'sub_msg')) {
            $accessToken->setSubMsg($resp->sub_msg);
        }
        if (property_exists($resp, 'log_id')) {
            $accessToken->setLogId($resp->log_id);
        }
        if (property_exists($resp, 'data')) {
            $accessToken->setData($resp->data);
        }
        
        return $accessToken;
    }

    public function isSuccess(): bool
    {
        return $this->code === 10000;
    }

    public function getAccessToken(): ?string
    {
        if ($this->data !== null && property_exists($this->data, 'access_token')) {
            return $this->data->access_token;
        }
        return null;
    }

    public function getExpireIn(): ?int
    {
        if ($this->data !== null && property_exists($this->data, 'expires_in')) {
            return $this->data->expires_in;
        }
        return null;
    }

    public function getRefreshToken(): ?string
    {
        if ($this->data !== null && property_exists($this->data, 'refresh_token')) {
            return $this->data->refresh_token;
        }
        return null;
    }

    public function getScope(): ?string
    {
        if ($this->data !== null && property_exists($this->data, 'scope')) {
            return $this->data->scope;
        }
        return null;
    }

    public function getShopId(): ?string
    {
        if ($this->data !== null && property_exists($this->data, 'shop_id')) {
            return $this->data->shop_id;
        }
        return null;
    }

    public function getShopName(): ?string
    {
        if ($this->data !== null && property_exists($this->data, 'shop_name')) {
            return $this->data->shop_name;
        }
        return null;
    }

    public function getShopBizType(): ?int
    {
        if ($this->data !== null && property_exists($this->data, 'shop_biz_type')) {
            return $this->data->shop_biz_type;
        }
        return null;
    }

    // Getters and Setters
    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): void
    {
        $this->code = $code;
    }

    public function getMsg(): ?string
    {
        return $this->msg;
    }

    public function setMsg(?string $msg): void
    {
        $this->msg = $msg;
    }

    public function getSubCode(): ?string
    {
        return $this->subCode;
    }

    public function setSubCode(?string $subCode): void
    {
        $this->subCode = $subCode;
    }

    public function getSubMsg(): ?string
    {
        return $this->subMsg;
    }

    public function setSubMsg(?string $subMsg): void
    {
        $this->subMsg = $subMsg;
    }

    public function getLogId(): ?string
    {
        return $this->logId;
    }

    public function setLogId(?string $logId): void
    {
        $this->logId = $logId;
    }

    public function getData(): ?object
    {
        return $this->data;
    }

    public function setData(?object $data): void
    {
        $this->data = $data;
    }
} 