<?php

class ResellerclubRegisterApi extends RegistrarCommon implements RegistrarInterface
{
    protected $auth_userid = '';
    protected $auth_password = '';
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
        $this->auth_userid = $config['auth_userid'];
        $this->auth_password = $config['auth_password'];
        $this->test_mode = isset($config['test_mode']) ? $config['test_mode'] : false;
    }

    /**
     * @param string $domain (test.com)
     * @return bool
     */
    public function isDomainAvailable($domain)
    {
        list($sld, $tld) = $this->parseDomain($domain);

        $params = array(
            'domain-name'           =>  $sld,
            'tlds'                  =>  array($tld),
            'suggest-alternative'   =>  false,
        );

        $result = $this->_makeRequest('domains/available', $params);

        $domain = strtolower($params['domain-name'].'.'.$params['tlds'][0]);
        if(isset($result->{$domain}->status)) {
            return ($result->{$domain}->status == 'available');
        }
        return false;
    }

    /**
     * @param string $domain
     * @param array $config
     * @param bool $exact_match
     * @return array
     * @throws RegistrarApiException
     *
     * @scenarios:
     * When tld-only is not specified, and exact-match is false: Results will include keyword matches and alternatives against all TLDs the reseller is signed up for.
     * When tld-only is specified, and exact-match is false: Results will include keyword matches and alternatives against only the TLDs specified.
     * When tld-only is not specified, and exact-match is true: Results will include keyword matches against all TLDs that the reseller is signed up for. No keyword alternatives will be returned.
     * When tld-only is specified, and exact-match is true: Results will include keyword matches against only the TLDs specified. No keyword alternatives will be returned.
     */
    public function getSuggestions($domain, array $config) {
        list($sld, $tld) = $this->parseDomain($domain);

        $params = array(
            'keyword' => $sld, // Allowed characters are a-z, A-Z, 0-9, space and hyphen.
            'exact-match' => isset($config['exact_match']) ? $config['exact_match'] : false, // true || false
        );
        if(isset($config['tld_only']) && $config['tld_only']) {
            $params['tld-only'] = '.'.$tld;
        }

        $result = $this->_makeRequest('domains/v5/suggest-names', $params);


        $list = array();
        foreach($result as $key => $value) {
            $list[] = $key;
        }

        return $list;
    }

    private function _getApiUrl()
    {
        if($this->test_mode) {
            return 'https://test.httpapi.com/api/';
        }
        return 'https://httpapi.com/api/';
    }

    protected function _makeRequest($url ,$params = array(), $method = 'GET', $type = 'json', $array = false)
    {
        $params = array_merge(array(
            'auth-userid'   =>  $this->auth_userid,
            'api-key' =>  $this->auth_password,
        ), $params);

        $opts = array(
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_URL             => $this->_getApiUrl().$url.'.'.$type,
        );

        if($method == 'POST') {
            $opts[CURLOPT_POST]         = 1;
            $opts[CURLOPT_POSTFIELDS]   = $this->_formatParams($params);
        } else {
            $opts[CURLOPT_URL]  = $opts[CURLOPT_URL].'?'.$this->_formatParams($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        if ($result === false) {
            if($this->test_mode) {
                $debug_url = $opts[CURLOPT_URL];
                if(isset($opts[CURLOPT_POSTFIELDS])) {
                    $debug_url .= '?'.$opts[CURLOPT_POSTFIELDS];
                }
                $e = new RegistrarApiException(sprintf('CurlException: "%s" Result: %s Url: %s', curl_error($ch), $result, $debug_url));
            } else {
                $e = new RegistrarApiException(sprintf('CurlException: "%s"', curl_error($ch)));
            }
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);
        return $this->_parseResponse($result, $type, $array);
    }

    /**
     * @param $result
     * @param string $type
     * @param bool|false $array
     * @return mixed
     * @throws RegistrarApiException
     */
    private function _parseResponse($result, $type = 'json', $array = false)
    {
        $json = json_decode($result);
        if(!$json instanceof stdClass) {
            return $result;
        }

        if(isset($json->status) && $json->status == 'ERROR') {
            throw new RegistrarApiException($json->message);
        }

        if(isset($json->status) && $json->status == 'error') {
            throw new RegistrarApiException($json->error);
        }

        if(isset($json->status) && $json->status == 'Failed') {
            throw new RegistrarApiException($json->actionstatusdesc);
        }

        if($array) {
            return json_decode($result, 1);
        } else {
            return $json;
        }
    }

    private function _formatParams($params)
    {
        foreach($params as $key => &$param) {
            if(is_bool($param)) {
                $param = ($param) ? 'true' : 'false';
            }
        }

        $params = http_build_query($params, null, '&');
        $params = preg_replace('~%5B(\d+)%5D~', '', $params);
        return $params;
    }
}