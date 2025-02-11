<?php

namespace Warexo\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

class ThankYouController extends ThankYouController_parent
{
    public function render()
    {
        $tpl = parent::render();
        $oConf = Registry::getConfig();
        unset($_COOKIE['wwagentparameter']);
        setcookie('wwagentparameter', null, -1, '/');
        if ($oConf->getConfigParam('sWAWIClientIdent'))
        {
            $oOrder = $this->getOrder();
            $this->_aViewData['sepaCreditorNumber'] = $oConf->getConfigParam('sWAWISepaCreditorNumber');
            $this->_aViewData['sepaMandate'] = strtoupper(substr($oConf->getConfigParam('sWAWIClientIdent'),0,3)).$oOrder->oxorder__oxordernr->value;
        }
        return $tpl;
    }
}