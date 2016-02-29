<?php
interface RegistrarInterface {
    public function isDomainAvailable($domain);
    public function getSuggestions($domain, $tld_only, $exact_match);
}