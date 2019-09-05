<?php
namespace MyOperator\Crm;

use MyOperator\Crm\TokenHandler;
use MyOperator\Transport;

abstract class CrmProvider
{

    public function __construct($company_id) {
        $this->company_id = $company_id;
    }

    public function setTokenHandler(TokenHandler $tokenHandler) {
        $this->tokenHandler = $tokenHandler;
        $this->tokenHandler->setRefreshMethod(function ($client_id, $client_secret, $refresh_token) {
            return $this->refreshToken($client_id, $client_secret, $refresh_token);
        });
    }

    public function __call($method, $args)
    {
        if(!in_array($method, [
            'refreshToken',
            'getOauthTokenKey',
            'getClientId',
            'getClientSecret'
        ])) {
            try {
                call_user_func_array($method, $args);
            } catch(\Exception $e) {
                // This probably means unauthorized
                if($e->getCode() == 401) {
                    $this->tokenHandler->refreshToken();
                    call_user_func_array($method, $args);
                } else {
                    throw $e;
                }
            }
        }
    }

    /**  As of PHP 5.3.0  */
    public static function __callStatic($method, $args)
    {
        echo "Unimplemented:: '$name' ";
    }

    abstract public function refreshToken($client_id, $client_secret, $refresh_token);


    public function setTransport(Transport $transport) {
        $this->transport = $transport;
    }

    public function getTransport() {
        return $this->transport;
    }
    
    abstract protected function getOauthTokenKey();

    abstract protected function getClientId();

    abstract protected function getClientSecret();

    protected function setHeaders($key, $val) {
        $this->transport->setHeaders([$key => $val]);
    }

    protected function setAuthHeaders() {
        $oauth_token_key = $this->getOauthTokenKey();
        $access_token = $this->tokenHandler->getAccessToken($this->company_id);
        $this->transport->setHeaders(['Authorization' => "{$oauth_token_key} {$access_token}"]);
    }

}
