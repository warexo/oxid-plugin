<?php

namespace Warexo\Application\Model;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\UtilsServer;

class Order extends Order_parent
{
    //protected $_aSkipSaveFields = array('oxorderdate','oxtimestamp');
    public $resetTimestamp;

    public function save()
    {
        $this->oxorder__wwpending = new \OxidEsales\Eshop\Core\Field(1);
        if ($this->oxorder__oxtransstatus->rawValue == 'ERROR' || $this->oxorder__oxtransstatus->rawValue == 'NOT_FINISHED')
        {
            if (!$this->oxorder__wwuseragent->value && Registry::get(UtilsServer::class)->getServerVar('HTTP_USER_AGENT'))
            {
                $this->oxorder__wwuseragent = new \OxidEsales\Eshop\Core\Field(Registry::get(UtilsServer::class)->getServerVar('HTTP_USER_AGENT'));
            }
            if (@$_COOKIE['wwagentparameter'])
            {
                $this->oxorder__wwagentparameter = new \OxidEsales\Eshop\Core\Field($_COOKIE['wwagentparameter']);
            }
        }
        $blRes = parent::save();
        if ($blRes)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("update oxorder set wwpending=0 where oxid=".$oDb->quote($this->getId()));

        }
        if ($this->resetTimestamp)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("update oxorder set oxtimestamp='0000-00-00 00:00:00' where oxid=".$oDb->quote($this->getId()));
        }
        return $blRes;
    }

    public function validateVouchers($basket)
    {

    }
}