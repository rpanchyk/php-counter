<?php

include_once dirname(__FILE__) . '/counter.php';
$ftc = new FireTrotCounter();
$bIsUseGeoIp = true;
$ftc->Install($bIsUseGeoIp);

?>