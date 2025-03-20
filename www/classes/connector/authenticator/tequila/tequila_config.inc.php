<?php

global $aConfig;
$aConfig = [
    'sSessionsDirectory' => '/tmp/',
    'iTimeout' => 86400,
];

/********************************************************
          DO NOT EDIT UNDER THIS LINE
 ********************************************************/
function GetConfigOption($sOption, $sDefault = '')
{
    global $aConfig;
    if (!array_key_exists($sOption, $aConfig)) {
        return ($sDefault);
    } else {
        return ($aConfig[$sOption]);
    }
}
