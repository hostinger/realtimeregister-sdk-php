<?php

class RegistrarCommon
{
    public function parseDomain($domain) {
        $domainParts = explode('.', $domain);
        if(count($domainParts) > 3 || count($domainParts) < 2) {
            throw new RegistrarApiException('Invalid domain name.');
        }

        $tld = $domainParts[1];
        if(isset($domainParts[2])) {
            $tld .= '.'.$domainParts[2];
        }

        return array($domainParts[0], $tld);
    }
}