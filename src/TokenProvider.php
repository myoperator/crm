<?php
namespace \MyOperator\Crm;

interface TokenProvider {
    /**
     * Fetch Client id and secrets
     **/
    public function getClientId();
    public function getClientSecret();

    /**
     * Fetch access token and refresh token by company id
     **/
    public function getAccessToken($company_id);
    public function getRefreshToken($company_id);

    /**
     * Update access token for company
     *
     * $expiry 0 means non-expiring token. unit in seconds
     **/
    public function setAccessToken($company_id, $access_token);
    public function setRefreshToken($company_id, $refresh_token);
}
