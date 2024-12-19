<?php

class agConfig extends oxConfig
{
    private static $sVersion;
    
    public static function isVersionOrHigher($ver)
    {
        if (!self::$sVersion)
        {
            self::$sVersion = oxDb::getDb()->getOne("select oxversion from oxshops where oxid='oxbaseshop' or oxid=1");
        }
        return version_compare(self::$sVersion, $ver) >= 0;
    }
    
    public static function getParameter( $sName, $blRaw = false )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return agConfig::getInstance()->getRequestParameter( $sName, $blRaw );
        else
            return parent::getParameter($sName, $blRaw);
    }
    
    public static function getInstance() {
        return AGF::getConfig();
    }
    
    public static function checkSpecialChars( & $sValue, $aRaw = null )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return agConfig::getInstance()->checkParamSpecialChars( $sValue, $aRaw );
        else
            return parent::checkSpecialChars($sValue, $aRaw);
    }
}