<?php
namespace MyOperator\Crm;

use \MyOperator\Transport;

abstract class CrmProvider
{
    private $tokenHandler;
    private $tokenprovider;

    public function __construct($company_id) {
        $this->company_id = $company_id;
    }

    public function setTokenProvider(\MyOperator\Crm\TokenProvider $provider)
    {
        $this->tokenprovider = $provider;
    }

    public function getTokenProvider() 
    {
        return $this->tokenprovider;
    }

    private function getTokenHandler() {
        $tokenprovider = $this->getTokenProvider();
        if(!$tokenprovider) {
            throw new \Exception(
                "Token Provider is not set\n Provide a provider which implements \MyOperator\Crm\TokenProvider class");
        }
        if(!$this->tokenHandler) {
            $this->tokenHandler = new \MyOperator\Crm\TokenHandler($this->company_id);
            $this->tokenHandler->setProvider($tokenprovider);
            $this->tokenHandler->setRefreshMethod(function ($client_id, $client_secret, $refresh_token) {
                return $this->refreshToken($client_id, $client_secret, $refresh_token);
            });
        }
        return $this->tokenHandler;
    }

    public function __call($method, $args)
    {
        if(!in_array($method, ['refreshToken','getOauthTokenKey'])) {
            try {
                $this->setAuthHeaders();
                return call_user_func_array([$this, $method], $args);
            } catch(\Exception $e) {
                // This probably means unauthorized
                if($e->getCode() == 401) {
                    $this->getTokenHandler()->refreshToken();
                    return call_user_func_array([$this, $method], $args);
                } else {
                    throw $e;
                }
            }
        }
    }

    abstract public function refreshToken($client_id, $client_secret, $refresh_token);


    public function setTransport(Transport $transport) {
        $this->transport = $transport;
    }

    public function getTransport($baseurl = null) {
        if(!$this->transport) 
            $this->transport = new Transport($baseurl);
        return $this->transport;
    }
    
    public function getOauthTokenKey() {
        return 'Bearer';
    }

    protected function setHeaders($key, $val) {
        $this->transport->setHeaders([$key => $val]);
    }

    protected function getAccessToken() {
        return $this->getTokenHandler()->getAccessToken($this->company_id);
    }

    protected function setAuthHeaders() {
        $transport = $this->getTransport();
        $oauth_token_key = $this->getOauthTokenKey();
        $access_token = $this->getTokenHandler()->getAccessToken($this->company_id);
        $this->transport->setHeaders(['Authorization' => "{$oauth_token_key} {$access_token}"]);
    }

}
