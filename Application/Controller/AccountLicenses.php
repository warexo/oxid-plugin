<?php

namespace Warexo\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class AccountLicenses extends \OxidEsales\Eshop\Application\Controller\AccountController
{
    protected $_sThisTemplate = 'account_licenses';

    public function init()
    {
        parent::init();
    }

    public function oeGdprBaseGetReviewAndRatingItemsCount()
    {
        return 0;
    }

    public function render()
    {
        parent::render();
        return $this->_sThisTemplate;
    }

    public function getBreadCrumb()
    {
        $aPaths = [];
        $aPath = [];
        $oUtils = Registry::getUtilsUrl();
        $iBaseLanguage = Registry::getLang()->getBaseLanguage();
        $sSelfLink = $this->getViewConfig()->getSelfLink();

        $aPath['title'] = Registry::getLang()->translateString('MY_ACCOUNT', $iBaseLanguage, false);
        $aPath['link'] = Registry::getSeoEncoder()->getStaticUrl($sSelfLink . 'cl=account');
        $aPaths[] = $aPath;

        $aPath['title'] = Registry::getLang()->translateString('ACCOUNT_LICENSES', $iBaseLanguage, false);
        $aPath['link'] = $oUtils->cleanUrl($this->getLink(), ['fnc']);
        $aPaths[] = $aPath;

        return $aPaths;
    }
}