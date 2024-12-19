<?php

class agSession extends oxSession
{
    public static function setVar( $name, $value )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return AGF::getSession()->setVariable( $name, $value );
        else
            return parent::setVar ($name, $value);
    }
    
    public static function getVar( $name )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return AGF::getSession()->getVariable( $name);
        else
            return parent::getVar ($name);
    }
    
    public static function hasVar( $name )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return AGF::getSession()->hasVariable( $name);
        else
            return parent::hasVar ($name);
    }
    
    public static function deleteVar( $name )
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
            return AGF::getSession()->deleteVariable( $name);
        else
            return parent::deleteVar ($name);
    }
    
    public static function getInstance() {
        return AGF::getSession();
    }
}