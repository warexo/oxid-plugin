<?php

namespace Warexo\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Request;

class ViewConfig extends ViewConfig_parent
{
    public function isAggroExtranetActive()
    {
        $oConfig = Registry::getConfig();
        return SettingsHelper::getBool('warexo', 'extranetactive');
    }

    public function isAggroExtranetOrdersActive()
    {
        $oConfig = Registry::getConfig();
        return SettingsHelper::getBool('warexo', 'extranetordersactive');
    }

    public function isAggroExtranetTicketsActive()
    {
        $oConfig = Registry::getConfig();
        return SettingsHelper::getBool('warexo', 'extranetticketsactive');
    }

    public function isAggroExtranetLicensesActive()
    {
        $oConfig = Registry::getConfig();
        return SettingsHelper::getBool('warexo', 'extranetlicensesactive');
    }

    public function isAggroExtranetSubscriptionContractsActive()
    {
        $oConfig = Registry::getConfig();
        return SettingsHelper::getBool('warexo', 'extranetsubscriptioncontractsactive');
    }
}