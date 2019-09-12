<?php
namespace MyOperator\Crm;

use \MyOperator\Crm\TokenProvider;

class TokenHandler
{

    const TIMEOUT = 3600; //1 Hour

    public function __construct($company_id) {
        $this->company_id = $company_id;
    }

    public function setProvider(TokenProvider $provider) {
        $this->provider = $provider;
    }

    public function getAccessToken() {
        $access_token = $this->provider->getAccessToken($this->company_id);
        if(!$access_token) {
            $access_token = $this->refreshToken();
        }
        return $access_token;
    }

    public function setRefreshMethod(callable $callback) {
        $this->refreshCallback = $callback;
    }

    public function refreshToken() {
        $refresh_token = $this->getRefreshToken();
        $client_id = $this->provider->getClientId();
        $client_secret = $this->provider->getClientSecret();
        if(!$this->refreshCallback) return null;
        try{
            $access_token = $this->refreshCallback->__invoke($client_id, $client_secret, $refresh_token);
            if(is_array($access_token) && array_key_exists('access_token', $access_token)) {
                $access_token = isset($access_token['access_token']) ? $access_token['access_token'] : null;
                $timeout = isset($access_token['timeout']) ? $access_token['timeout'] : self::TIMEOUT;
            }
            
            if($access_token !== null) {
                $this->provider->setAccessToken($this->company_id, $access_token, $timeout);
                return $access_token;
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return null;
    }

    public function getRefreshToken() {
        return $this->provider->getRefreshToken($this->company_id);
    }
}
