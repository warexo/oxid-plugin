<?php

class AGF
{
    
    protected static $_oxRegistryExists = null;
    
    public static function get($sClassName)
    {
        /*$obj = @call_user_func($sClassName.'::getInstance');
        if ($obj)
            return $obj;*/
        if (self::$_oxRegistryExists === null)
        {
            self::$_oxRegistryExists = class_exists("oxregistry");
        }
        if (self::$_oxRegistryExists)
            return oxRegistry::get($sClassName);
        else
            return call_user_func($sClassName.'::getInstance');
    }
    
    public static function getConfig()
    {
        return self::get("oxConfig");
    }

    
    public static function getSession()
    {
        return self::get("oxSession");
    }

    
    public static function getLang()
    {
        return self::get("oxLang");
    }

    public static function getUtils()
    {
        return self::get("oxUtils");
    }
    
    public static function getShopConfVar($sVar,$sShopId=null,$sModule=null){
        if(version_compare(self::getVersion(), '4.5.5', '>=')){
            return self::getConfig()->getShopConfVar($sVar,$sShopId,$sModule);
        }else{
            return self::getConfig()->getShopConfVar($sVar,$sShopId);
        }
    }
    
    public static function saveShopConfVar($sVarType, $sVarName, $sVarVal, $sShopId = null, $sModule = ''){
        return self::getConfig()->saveShopConfVar($sVarType,$sVarName,$sVarVal,$sShopId,$sModule);
    }
    
    public static function getActiveShop(){
        return self::getConfig()->getActiveShop();
    }
    
    public static function isUtf(){
        return self::getConfig()->isUtf();
    }
    
    public static function getVersion(){
        return self::getConfig()->getVersion();
    }
}