<?php

class RealtimeRegisterApi implements RegistrarInterface
{
    protected $dealer = '';
    protected $password = '';
    protected $test_mode = '';

    /**
     * $config['dealer'] string
     * $config['password'] string
     * $config['test_mode'] bool
     *
     * @param array $config (See above)
     */
    public function __construct($config)
    {
        $this->dealer = $config['dealer'];
        $this->password = $config['password'];
        $this->test_mode = isset($config['test_mode']) ? $config['test_mode'] : false;
    }

    /**
     * @param string $domain (test.com)
     * @return bool
     */
    public function isDomainAvailable($domain)
    {
        try {
            $response = $this->_sendRequest("domains/" . urlencode($domain) . "/check", array());
        } catch (Exception $e) {
            return false;
        }

        if (isset($response->response->$domain->avail)) {
            return $response->response->$domain->avail == 1 ? true : false;
        }

        return false;
    }

    public function getSuggestions($domain, $tld_only = false, $exact_match = false) {
        return array();
    }

    /**
     * @return string
     */
    private function _getApiUrl($action)
    {
        if ($this->test_mode) {
            return "https://http.api.yoursrs-ote.com/v1/" . $action;
        }
        return "https://http.api.yoursrs.com/v1/" . $action;
    }

    /**
     * @param string $action
     * @param array $params
     * @return stdClass
     * @throws RealtimeRegisterApiException
     */
    private function _sendRequest($action, $params = array())
    {
        $params['login_handle'] = $this->dealer;
        $params['login_pass'] = $this->password;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->_getApiUrl($action));
        curl_setopt($curl, CURLOPT_FAILONERROR, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

        $result = curl_exec($curl);

        /* Could not connect to API, curl returned false */
        if ($result === false) {
            curl_close($curl);
            throw new RealtimeRegisterApiException("Could not connect to RealtimeRegister API.");
        }

        /* Try to decode the response */
        $response = json_decode($result);
        curl_close($curl);

        /* Response could not be decoded */
        if (!$response) {
            throw new RealtimeRegisterApiException("Received invalid response. Please try again.");
        }

        /* An error occurred */
        if ($response->code >= 2000) {
            throw new RealtimeRegisterApiException($response->msg);
        }

        return $response;
    }
}