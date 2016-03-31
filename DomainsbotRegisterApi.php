<?php

class DomainsbotRegisterApi extends RegistrarCommon implements RegistrarInterface
{
    protected $api_key = '';

    /**
     * $config['api_key'] string
     *
     * @param array $config (See above)
     */
    public function __construct($config)
    {
        $this->api_key = $config['api_key'];
    }

    /**
     * @param string $domain (test.com)
     * @return bool
     */
    public function isDomainAvailable($domain)
    {
        // registrar does not support domain availability check
        return false;
    }

    /**
     * @param $domain
     * @param array $config
     * @return array
     */
    public function getSuggestions($domain, array $config) {
        $params = array(
            'q' => $domain, // A domain, second level domain or a list of keywords.
        );

        /**
         * By default every generic TLD (i.e. com, xyz, club, but not country code TLDs) that is already available
         * for registration can be returned in the results. Passing a comma separated list of TLD in the 'tld_only'
         * parameter forces the API to use your selected TLDs only. E.g. tld_only=nyc,info
         */
        if(isset($config['tld_only'])) {
            $params['tld_only'] = $config['tld_only'];
        }

        /**
         * Use this parameter if you want to allow every generic TLD plus additional TLDs (i.e. .uk, .de).
         * Pass the TLDs (without the first dot) to add as a comma separated list. E.g: tld_ok=co.uk,uk,de
         */
        if(isset($config['tld_ok'])) {
            $params['tld_ok'] = $config['tld_ok'];
        }

        /**
         * Use this parameter if you want to allow every generic TLD with the exception of some TLDs (i.e. .com).
         * Pass the TLDs (without the first dot) to add as a comma separated list. E.g: tld_ok=nyc,info
         */
        if(isset($config['tld_no'])) {
            $params['tld_no'] = $config['tld_no'];
        }

        /**
         * Pass the end user IP address (both IPv4 and IPv6 are supported) to enable geolocalization of both TLDs
         * and variation.
         */
        if(isset($config['ip'])) {
            $params['ip'] = $config['ip'];
        }

        try {
            $response = $this->_makeRequest("recommend", $params);
        } catch (Exception $e) {
            return array();
        }

        $list = array();
        foreach($response as $suggestion) {
            $list[] = $suggestion['Domain'];
        }

        return $list;
    }

    /**
     * @return string
     */
    private function _getApiUrl()
    {
        return 'https://api-2445581410012.apicast.io:443/v5/';
    }

    /**
     * @param string $action
     * @param array $params
     * @return stdClass
     * @throws RegistrarApiException
     */
    protected function _makeRequest($action, $params = array())
    {
        $params = array_merge(array(
            'apikey' =>  $this->api_key,
        ), $params);

        $opts = array(
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 60,
            CURLOPT_URL             => $this->_getApiUrl().$action,
        );

        $opts[CURLOPT_URL]  = $opts[CURLOPT_URL].'?'.$this->_formatParams($params);

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        if ($result === false) {
            $e = new RegistrarApiException(sprintf('CurlException: "%s"', curl_error($ch)));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);

        $res = json_decode($result);

        // Validate $res?

        return $res;
    }

    private function _formatParams($params)
    {
        return http_build_query($params, null, '&');
    }
}