<?php
namespace MyOperator\Zoho;

interface TokenProvider {
    /**
     * Get API Version
     **/
    public function isApiV2($company_id);

    /**
     * Set Client id and secret
     */
    public function setClientId($authClientId);
    public function setClientSecret($authClientSecret);

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
    public function setAccessToken($company_id, $access_token, $expiry = 0);
    public function setRefreshToken($company_id, $refresh_token, $expiry = 0);
}
