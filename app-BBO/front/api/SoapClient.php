<?php
	// This file contains the soap client as well as the base request objects, languages and partnerCode

	$mSoapClient = new SoapClient("http://integration.livebookings.net/webservices/external/service.asmx?WSDL");
	$mPartnerCode = "SE-RES-STUREHOF_100238:4268"; // input your partnerCode here
	$mLanguages = "en-GB";

    // soap functions
    //var_dump($mSoapClient->__getFunctions());
    //var_dump($mSoapClient->__getTypes());
?>
