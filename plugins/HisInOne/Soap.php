<?php

namespace HisInOne;

class Soap
{

    static protected $instance = null;

    static public function get()
    {
        if (!self::$instance) {
            self::$instance = new SoapClient(\FleximportConfig::get("HISINONE_WSDL_URL"), array(
                'connection_timeout' => 1, //Zeit für den Verbindungsaufbau
                'trace' => true,
                'exceptions' => 0,
                'cache_wsdl' => ($GLOBALS['CACHING_ENABLE'] || !isset($GLOBALS['CACHING_ENABLE']))
                    ? WSDL_CACHE_DISK
                    : WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            ));

            $headerbody = new \SoapVar('<wsse:Security soapenv:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"><wsse:UsernameToken><wsse:Username>'.htmlReady(\FleximportConfig::get("HISINONE_SOAP_USERNAME")).'</wsse:Username><wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' .\FleximportConfig::get("HISINONE_SOAP_PASSWORD") .'</wsse:Password></wsse:UsernameToken></wsse:Security>', \XSD_ANYXML);
            $soapHeaders = new \SoapHeader(
                "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd",
                'Security',
                $headerbody
            );
            //self::$instance->__setSoapHeaders($soapHeaders);
            if (is_soap_fault(self::$instance)) {
                throw new Exception("SOAP-Error: " . self::$instance);
            }
        }
        return self::$instance;
    }
}
