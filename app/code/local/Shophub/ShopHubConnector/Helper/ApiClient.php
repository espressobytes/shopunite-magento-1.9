<?php

class Shophub_ShopHubConnector_Helper_ApiClient extends Shophub_ShopHubConnector_Helper_ApiClient_Authorization
{

    /** @var Shophub_ShopHubConnector_Model_ApiLog */
    protected $apiLog = null;

    /** @var int */
    public $lastStatusCode = null;

    /** @var string */
    public $lastResponseMsg = "";

    /**
     * @param $method
     * @param $route
     * @param $parameters
     */
    protected function initApiLog($method, $route, $parameters)
    {
        $this->apiLog = mage::getModel('shophubconnector/apiLog');
        $this->apiLog->setMethod($method);
        $this->apiLog->setRoute($route);
        $this->apiLog->setDateTime($this->getDateTime());
        $this->apiLog->setParameters(json_encode($parameters));
    }

    protected function logErrorAndSave($msg)
    {
        $this->apiLog->setErrorMessage($msg);
        $this->apiLog->save();
    }

    /**
     * Do API-Request to ShopHup
     * @param $method
     * @param $route
     * @param $parameters
     * @return bool
     */
    public function request($method, $route, $parameters)
    {
        $this->initApiLog($method, $route, $parameters);

        // validate request
        if (!$this->validateRequest($method, $route, $parameters)) {
            $this->logErrorAndSave('Validation Failed');
            return false;
        }

        // Get Token from authorization class (parent)
        $token = $this->getValidToken();
        if (!$token) {
            $logMessage = 'No valid Token.';
            if ($this->errorMessage) {
                $logMessage .= " " . $this->errorMessage;
            }
            $this->logErrorAndSave($logMessage);
            return false;
        }

        // do request with valid token
        $serviceUrl = $this->apiUrl . $route;

        $curl = $this->prepareCurlRequest($serviceUrl, $method, $parameters, $token);

        // Get response:
        $curlResponse = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (!$curlResponse || $statusCode == 401) {
            // if response is negative, try again with new token!
            curl_close($curl);
            $token = $this->getValidToken(true);
            $curl = $this->prepareCurlRequest($serviceUrl, $method, $parameters, $token);
            $curlResponse = curl_exec($curl);
        }

        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errors = curl_error($curl);
        curl_close($curl);

        $this->apiLog->setResponseStatusCode($statusCode);
        $this->apiLog->setResponseContent($curlResponse);

        $this->lastResponseMsg = "";
        if (is_string($curlResponse)) {
            $this->lastResponseMsg = $curlResponse;
        }

        $this->lastStatusCode = $statusCode;

        if ( !in_array($statusCode, array(200,201)) ) {
            $this->logErrorAndSave('Wrong Response... ' . $this->lastResponseMsg);
            return false;
        }

        $this->apiLog->save();
        return true;
    }

    private function prepareCurlRequest($serviceUrl, $method, $parameters, $token) {
        $curl = curl_init($serviceUrl);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
        } elseif ($method = "PUT") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
        }
        $header = array(
            'Content-type: application/json',
            'Authorization: Bearer ' . $token
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        return $curl;
    }

    /**
     * @param $method
     * @param $route
     * @param $parameters
     * @return bool
     */
    protected function validateRequest($method, $route, $parameters)
    {
        if (!in_array($method, array('POST', 'GET', 'PUT', 'DELETE'))) {
            return false;
        }
        if (!is_array($parameters)) {
            return false;
        }
        if (!is_string($route) || !$route) {
            return false;
        }
        return true;
    }

}