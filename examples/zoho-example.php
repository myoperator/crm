<?php

use \MyOperator\Crm\CrmProvider;


class ZohoCrmProvider extends CrmProvider {
    /* *
     * Use this function to return the value which will be set in OAuth Authorization
     * header. This will send the following header with each request
     * 
     * Ex- header('Authorization', 'Zoho-oauthtoken $token')
     * */
    function getOauthTokenKey() {
        return 'Zoho-oauthtoken';
    }

    /* *
     * Use this function to return the client id of your crm provider
     * */
    function getClientId() {
        return 'your-crm-client-id';
    }

    /* *
     * Use this function to return the client secret of your crm provider
     * */
    function getClientSecret() {
        return 'your-crm-client-secret';
    }

    /* *
     * Use this function to refresh the token as per your CRM implementation.
     * Return values should be either array ['access_token' => $token, 'timeout' => $timeout]
     * or simply a string of access token
     * 
     * @param $client_id Client id
     * @param $client_secret Client Secret
     * @param $refresh_token Refresh token
     * 
     * @return ['access_token' => $token, 'timeout' => $timeout] || $token
     * */
    function refreshToken($client_id, $client_secret, $refresh_token) {
        // This is only a sample implementation
        $response = $your_curl_lib->post("/token?refresh_token={$refresh_token}&client_id= {$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        return ['access_token' => $response['access_token'], 'timeout' => $response['expiry_in_secs']];
    }

    public function searchByPhone($phonenumber) {
        $criteria = "((Phone:equals:{$phonenumber})or(Mobile:equals:{$phonenumber}))";

        // You can use your own curl lib here as well
        $curl_lib = $this->getTransport();

        try { 
            $response = $curl_lib->get(
                self::CRM_BASE_URL . '/leads/search', 
                ['criteria' => $criteria]
            );
            if($response->getStatus() == 204) {
                return null;
            }
            return $response->json();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
