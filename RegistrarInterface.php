<?php
interface RegistrarInterface {
    public function isDomainAvailable($domain);
    public function getSuggestions($domain, array $config);
}