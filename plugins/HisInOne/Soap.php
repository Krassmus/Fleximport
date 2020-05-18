<?php

namespace HisInOne;

class Soap
{

    static protected $instance = null;

    static public function get()
    {
        if (!self::$instance) {

            \FleximportConfig::get("HISINONE_SOAP_ENDPOINT");

            $evasys_wsdl = \FleximportConfig::get("HISINONE_WSDL_URL");
            //$evasys_user = \Config::get()->EVASYS_USER;
            //$evasys_password = \Config::get()->EVASYS_PASSWORD;

            self::$instance = new SoapClient($evasys_wsdl, array(
                'connection_timeout' => 1, //Zeit für den Verbindungsaufbau
                'trace' => true,
                'exceptions' => 0,
                'cache_wsdl' => ($GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE']))
                    ? WSDL_CACHE_DISK
                    : WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            ));
            $soapHeaders = new \SoapHeader("wsse", 'Security', array(
                'UsernameToken' => array(
                    'Username' => \FleximportConfig::get("HISINONE_SOAP_USERNAME"),
                    'Password' => \FleximportConfig::get("HISINONE_SOAP_PASSWORD")
                )
            ));
            self::$instance->__setSoapHeaders($soapHeaders);
            if (is_soap_fault(self::$instance)) {
                throw new Exception("SOAP-Error: " . self::$instance);
            }
        }
        return self::$instance;
    }
}
