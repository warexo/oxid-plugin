<?php

namespace Warexo\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class AccountSubscriptionContract extends \OxidEsales\Eshop\Application\Controller\AccountController
{
    protected $_sThisTemplate = 'account_subscription_contract';

    public function init()
    {
        if (Registry::get(Request::class)->getRequestParameter('contractid') && !$this->getUser())
        {
            setcookie('agcontractid', Registry::get(Request::class)->getRequestParameter('contractid'));
        }
        parent::init();
    }

    public function oeGdprBaseGetReviewAndRatingItemsCount()
    {
        return 0;
    }

    public function render()
    {
        parent::render();
        $contractid = Registry::get(Request::class)->getRequestParameter('contractid');
        if (!Registry::get(Request::class)->getRequestParameter('contractid') && $_COOKIE['agcontractid'])
        {
            $contractid = $_COOKIE['agcontractid'];
            setcookie('agcontractid', '');
        }
        $this->_aViewData['route'] = 'accountsubscriptioncontract/edit/'.$contractid;

        return $this->_sThisTemplate;
    }
}