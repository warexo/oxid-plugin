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
        return $oConfig->getShopConfVar('extranetactive', null, 'module:warexo');
    }

    public function isAggroExtranetOrdersActive()
    {
        $oConfig = Registry::getConfig();
        return $oConfig->getShopConfVar('extranetordersactive', null, 'module:warexo');
    }

    public function isAggroExtranetTicketsActive()
    {
        $oConfig = Registry::getConfig();
        return $oConfig->getShopConfVar('extranetticketsactive', null, 'module:warexo');
    }

    public function isAggroExtranetLicensesActive()
    {
        $oConfig = Registry::getConfig();
        return $oConfig->getShopConfVar('extranetlicensesactive', null, 'module:warexo');
    }

    public function isAggroExtranetSubscriptionContractsActive()
    {
        $oConfig = Registry::getConfig();
        return $oConfig->getShopConfVar('extranetsubscriptioncontractsactive', null, 'module:warexo');
    }
}