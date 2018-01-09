<?php

class Shophub_ShopHubConnector_Helper_ApiClient_Authorization extends Shophub_ShopHubConnector_Helper_Data
{

    /** @var string */
    protected $apiUrl;

    /** @var string */
    protected $apiUsername;

    /** @var int */
    protected $tokenStatusCode;

    /** @var string */
    protected $errorMessage = "";

    /** @var string */
    protected $token;

    /** @var int [seconds] */
    protected $tokenLifetime = null;

    /** @var int [seconds] */
    protected $curlTimeout;

    public function __construct()
    {
        $this->curlTimeout = $this->getConfigValue('dev/curl_timeout');
        if (!is_numeric($this->curlTimeout)) {
            $this->curlTimeout = 30;
        }
        $this->apiUrl = $this->getConfigValue('general/apiurl');
    }

    /**
     * @param bool $generateNewToken
     * @return bool|string
     */
    public function getValidToken($generateNewToken = false)
    {
        $lastTokenObj = $this->getLastTokenObj();
        if ($this->isTokenObjValid($lastTokenObj) && !$generateNewToken) {
            $this->token = $lastTokenObj->getToken();
        } else {
            $token = $this->generateNewToken();
            if (!$token) {
                return false;
            }
            $this->token = $token;
            $this->saveTokenToDb();
        }
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getLastTokenObj()
    {
        $collection = Mage::getModel('shophubconnector/accessToken')->getCollection();
        /** @var $accessTokenModel Shophub_ShopHubConnector_Model_AccessToken */
        $lastTokenObj = $collection->getLastItem();
        return $lastTokenObj;
    }

    /**
     * @param $tokenObj Shophub_ShopHubConnector_Model_AccessToken
     * @return bool
     */
    public function isTokenObjValid($tokenObj)
    {
        $validUntil = $tokenObj->getValidUntil();
        $validUntilTimeStamp = strtotime($validUntil);
        if (!$validUntilTimeStamp) {
            return false;
        }
        $nowTimeStamp = $this->getCurrentTimeStamp();
        return $validUntilTimeStamp > ($nowTimeStamp + 120);
    }

    /**
     * @return bool|string
     */
    public function generateNewToken()
    {
        $route = "/tokens";

        $this->apiUsername = $this->getConfigValue('general/username');
        $password = $this->getConfigValue('general/password');

        // Init and set curl options:
        $serviceUrl = $this->apiUrl . $route;
        $curl = curl_init($serviceUrl);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiUsername . ":" . $password);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlTimeout);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json')
        );

        // Get curl response:
        $curlResponse = curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $this->tokenStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errors = curl_error($curl);
        curl_close($curl);

        // if that method did not work, try to get token with alternative method:
        if ($this->tokenStatusCode != 200) {

            $this->errorMessage = "Error: Could not authorize to route $serviceUrl. Response status code: " . $this->tokenStatusCode;
            $this->errorMessage .= ". Curl-Response: " . json_encode($curlResponse);
            $curlErrorStr = isset($errors) ? json_encode($errors) : 'not set';
            $this->errorMessage .= ". Curl-Errors: " . $curlErrorStr;

            $curl = curl_init($serviceUrl);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->curlTimeout);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, "");
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->apiUsername . ":" . $password)
            ));

            // try new curl-options
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

            $curlResponse = curl_exec($curl);
            $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            $this->tokenStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $errors = curl_error($curl);
            curl_close($curl);

            // check final response:
            if ($this->tokenStatusCode != 200) {
                // TODO: log error
                $this->errorMessage .= " --- Second try to get token failed as well! Details: ";
                $this->errorMessage .= "Curl-Response: " . json_encode($curlResponse);
                $curlErrorStr = isset($errors) ? json_encode($errors) : 'not set';
                $this->errorMessage .= " --- Curl-Errors: " . $curlErrorStr;
                return false;
            } else {
                $this->errorMessage = "";
            }
        }

        $responseArr = json_decode($curlResponse, true);

        // Extract Token:
        if (!isset($responseArr['token'])) {
            $this->errorMessage = "Error: Token not send in response";
            $this->errorMessage .= ". Curl-Response: " . $curlResponse;
            $curlErrorStr = isset($errors) ? json_encode($errors) : 'not set';
            $this->errorMessage .= ". Curl-Errors: " . $curlErrorStr;
            return false;
        }
        $token = $responseArr['token'];

        // Extract Token-Lifetime
        if (isset($responseArr['tokenLifetime'])) {
            $this->tokenLifetime = $responseArr['tokenLifetime'];
        } else {
            // use standard token lifetime:
            $this->tokenLifetime = 23.5 * 60 * 60;
        }

        return $token;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function saveTokenToDb()
    {
        /** @var $accessTokenModel Shophub_ShopHubConnector_Model_AccessToken */
        $accessTokenModel = Mage::getModel('shophubconnector/accessToken');
        $accessTokenModel->setToken($this->token);
        $accessTokenModel->setCreatedAt($this->getDateTime());
        if ($this->tokenLifetime) {
            $validUntilTimeStamp = $this->getCurrentTimeStamp() + $this->tokenLifetime;
            $accessTokenModel->setValidUntil($this->getDateTime($validUntilTimeStamp));
        }
        if ($this->apiUsername) {
            $accessTokenModel->setApiUsername($this->apiUsername);
        }
        $accessTokenModel->save();
        return $accessTokenModel->getId();
    }

}