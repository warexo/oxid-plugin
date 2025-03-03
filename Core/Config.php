<?php

namespace Warexo\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Request;

class Config extends Config_parent
{
    public function getConfigParam($sName, $default = null)
    {
        if ($default !== NULL)
            $ret = parent::getConfigParam($sName, $default);
        else
            $ret = parent::getConfigParam($sName);

        //if b2b config param
        if ($sName == "blShowNetPrice" && $this->getShopConfVar('wawishownetpriceforgroups'))
        {
            if ($this->wwShowNetPrice())
            {
                return true;
            }
        }
        if ($sName == "blEnterNetPrice" && $this->getShopConfVar('wawienternetpriceforgroups'))
        {
            if ($this->wwShowNetPrice())
            {
                return true;
            }
        }
        return $ret;
    }

    /**
     * Checks if user is in dealer group
     *
     * @return boolean
     */
    public function wwShowNetPrice()
    {
        $oUser = $this->getUser();
        // user is a dealer
        if ($oUser) {
            $groups = $oUser->getUserGroups();
            foreach ($groups as $group)
                if ($group->oxgroups__wwnettomode->value)
                    return true;
            return false;
        }
    }
}