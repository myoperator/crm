<?php

include_once 'vendor/autoload.php';

use \MyOperator\Crm\CrmProvider;


class ZohoCrm extends CrmProvider {

    const CRM_BASE_URL = 'https://www.zohoapis.in/crm/v2';
    const ZOHO_OAUTH_URL = 'https://accounts.zoho.in/oauth/v2';

    /* *
     * We are overriding this method since zoho uses 'Zoho-oauthtoken'
     * as bearer in header
     * */
    function getOauthTokenKey() {
        return 'Zoho-oauthtoken';
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
        $transport = $this->getTransport();
        $response = $transport->post("https://accounts.zoho.in/oauth/v2/token?refresh_token={$refresh_token}&client_id={$client_id}&client_secret={$client_secret}&grant_type=refresh_token");
        $response =  $response->json();
        return ['access_token' => $response['access_token'], 'timeout' => $response['expiry_in_secs']];
    }

    protected function searchByPhone($phonenumber) {
        $criteria = "((Phone:equals:{$phonenumber})or(Mobile:equals:{$phonenumber}))";

        // You can use your own curl lib here as well
        $curl_lib = $this->getTransport();

        try { 
            $response = $curl_lib->get(
                'https://www.zohoapis.in/crm/v2/leads/search', 
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
