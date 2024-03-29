<?php

use MyOperator\Crm\TokenProvider;
use MyOperator\Transport;

class APITokenProvider implements TokenProvider {

    private $authClientId;
    private $authClientSecret;

    public function __construct($host='http://localhost') {
        $this->host = $host;
        $this->client = $this->getHTTPClient();
    }

    public function setCrmKey($key) {
        $this->key = $key;
    }

    public function getHTTPClient($host = null) {
        if(!$host) $host = $this->host;
        return new Transport($host);
    }

    public function setHTTPClient(Transport $transport) {
        $this->client = $transport;
    }

    public function getAccessToken($company_id) {
        $response = $this->client->post('/memcache/get', ['data' => json_encode(['key' => "{$company_id}_{$this->key}_access_token"])])->json();
        return isset($response['status']) && ($response['status'] == 'success') ? $response['result']["{$company_id}_{$this->key}_access_token"] : null;
    }

    public function setAccessToken($company_id, $token, $expiry = null) {
        $response = $this->client->post('/memcache/engine_add', ['data' => json_encode(['key' => "{$company_id}_{$this->key}_access_token", 'value' => $token, 'expiry' => $expiry])])->json();
        return isset($response['status']) && ($response['status'] == 'success');
    }

    public function getRefreshToken($company_id) {
        $response = $this->client->post('/memcache/get', ['data' => json_encode(['key' => "{$company_id}_{$this->key}_refresh_token"])])->json();
        return isset($response['status']) && ($response['status'] == 'success') ? $response['result']["{$company_id}_{$this->key}_refresh_token"] : null;
    }

    public function setRefreshToken($company_id, $token, $expiry = 0) {
        $response = $this->client->post('/memcache/engine_add', ['data' => json_encode(['key' => "{$company_id}_{$this->key}_refresh_token", 'value' => $token, 'expiry' => $expiry])])->json();
        return isset($response['status']) && ($response['status'] == 'success');
    }

    public function getClientId() {
        return 'your-crm-client-id';
    }

    public function getClientSecret() {
        return 'your-crm-client-secret';
    }

    public function isCrmActive($company_id) {
        $response = $this->client->post('/memcache/get', ['data' => json_encode(['key' => "{$company_id}_{$this->key}_status"])])->json();
        if (isset($response['status']) && ($response['status'] == 'success')) {
            return ($response['result']["{$company_id}_{$this->key}_status"] == 1);
        }
        return false;
    }
}
