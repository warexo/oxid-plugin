<?php

namespace Warexo\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;

class AccountTicket extends \OxidEsales\Eshop\Application\Controller\AccountController
{
    protected $_sThisTemplate = 'account_ticket';

    public function init()
    {
        if (Registry::get(Request::class)->getRequestParameter('ticketid') && !$this->getUser())
        {
            setcookie('agticketid', Registry::get(Request::class)->getRequestParameter('ticketid'));
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
        $ticketid = Registry::get(Request::class)->getRequestParameter('ticketid');
        if (!Registry::get(Request::class)->getRequestParameter('ticketid') && $_COOKIE['agticketid'])
        {
            $ticketid = $_COOKIE['agticketid'];
            setcookie('agticketid', '');
        }
        $this->_aViewData['route'] = 'accountticket/edit/'.$ticketid;

        return $this->_sThisTemplate;
    }
}