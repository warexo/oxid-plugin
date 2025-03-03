<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\TableViewNameGenerator;

class Voucher extends Voucher_parent
{
    /**
     * Resolves the rest voucher by it's number, is a copy of getVoucherByNr with
     * small modifications
     * @param string $sVoucherNr
     * @param string $sUserId
     * @param array $aVouchers
     * @param boolean $blCheckavalability
     * @return oxVoucher
     * @throws oxVoucherException
     */
    public function getVoucherByNr($sVoucherNr, $aVouchers = [], $blCheckavalability = false)
    {
        $oRet = null;
        if (!is_null($sVoucherNr))
        {
            $sViewName = $this->getViewName();
            $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
            $sSeriesViewName = $tableViewNameGenerator->getViewName('oxvoucherseries');
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

            $sQ  = "select {$sViewName}.* from {$sViewName}, {$sSeriesViewName} where
                        {$sSeriesViewName}.oxid = {$sViewName}.oxvoucherserieid and
                        {$sViewName}.oxvouchernr = " . $oDb->quote( $sVoucherNr ) . " and ";

            if (is_array($aVouchers))
            {
                foreach ($aVouchers as $sVoucherId => $sSkipVoucherNr)
                {
                    $sQ .= "{$sViewName}.oxid != " . $oDb->quote( $sVoucherId ) . " and ";
                }
            }

            $sQ .= " {$sViewName}.agrestvalue > 0 ";

            if ($blCheckavalability )
            {
                $iTime = time() - 3600 * 3;
                $sQ .= " and {$sViewName}.oxreserved < '{$iTime}' ";
            }

            $sQ .= " limit 1";


            if (!($oRet = $this->assignRecord($sQ)))
            {
                $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\VoucherException::class);
                $oEx->setMessage('ERROR_MESSAGE_VOUCHER_NOVOUCHER');
                $oEx->setVoucherNr($sVoucherNr);
                throw $oEx;
            }
        }

        return $oRet;
    }

    /**
     * Override for mark as used, will store the rest value in oxvouchers__agrestvalue
     * @param string $sOrderId
     * @param string $sUserId
     * @param float $dDiscount
     */
    public function markAsUsed($sOrderId, $sUserId, $dDiscount)
    {
        parent::markAsUsed($sOrderId,$sUserId,$dDiscount);

        $oSerie = $this->getSerie();
        if ($oSerie->oxvoucherseries__oxdiscounttype->value == 'absolute' )
        {
            $dRestDiscount = $this->getRestDiscount($dDiscount);
            $this->oxvouchers__agrestvalue->setValue($dRestDiscount);
            $this->save();
            if ($dRestDiscount > 0)
                $this->unMarkAsReserved();

        }
    }

    protected $wasFixed = false;

    /**
     * Override to return a series if a restvalue is set
     * @return oxVoucherSerie
     */
    public function getSerie(){
        $oSerie = parent::getSerie();
        if($oSerie && $oSerie->oxvoucherseries__oxdiscounttype->value == 'absolute' && $this->oxvouchers__agrestvalue->value > 0 && !$this->wasFixed)
        {
            $oSerie->oxvoucherseries__oxdiscount->setValue($this->oxvouchers__agrestvalue->value);
            $this->wasFixed = true;
        }
        return $oSerie;
    }

    /**
     * Returns rest discount for this voucher
     * @param float $dDiscount
     * @return float
     */
    public function getRestDiscount($dDiscount)
    {
        $dRestDiscount = 0;
        $oSerie = $this->getSerie();
        if ($oSerie->oxvoucherseries__oxdiscounttype->value == 'absolute')
        {
            if ($this->oxvouchers__agrestvalue->value > 0)
            {
                $dRestDiscount = $this->oxvouchers__agrestvalue->value - $dDiscount;
            }
            else
            {
                $oCur = $this->getConfig()->getActShopCurrencyObject();
                $dFullDiscount = $oSerie->oxvoucherseries__oxdiscount->value * $oCur->rate;
                $dRestDiscount = $dFullDiscount - $dDiscount;
            }
        }
        return $dRestDiscount;
    }

    /**
     * Modified so this is usable with rest discounts
     * @param oxUser $oUser
     * @return boolean
     */
    protected function isAvailableInOtherOrder($oUser)
    {
        if ($this->oxvouchers__agrestvalue->value <= 0)
        {
            if (!$oUser || !$oUser->oxuser__oxid->value)
                return true;
            return parent::isAvailableInOtherOrder($oUser);
        }

        return true;
    }

    protected function isAvailablePrice($dPrice)
    {
        $oSeries = $this->getSerie();
        $oCur = \OxidEsales\Eshop\Core\Registry::getConfig()->getActShopCurrencyObject();
        if ( $oSeries->oxvoucherseries__oxminimumvalue->value && $dPrice < ($oSeries->oxvoucherseries__oxminimumvalue->value*$oCur->rate))
        {
            $oEx = oxNew(\OxidEsales\Eshop\Core\Exception\VoucherException::class);
            $oEx->setMessage('ERROR_MESSAGE_VOUCHER_INCORRECTPRICE');
            $oEx->setVoucherNr($this->oxvouchers__oxvouchernr->value);
            throw $oEx;
        }

        return true;
    }

    protected function getGenericDiscountValue( $dPrice )
    {
        $oSeries = $this->getSerie();
        if ($oSeries->oxvoucherseries__oxdiscounttype->value == 'absolute' )
        {
            $oCur = \OxidEsales\Eshop\Core\Registry::getConfig()->getActShopCurrencyObject();
            $dDiscount = $oSeries->oxvoucherseries__oxdiscount->value * $oCur->rate;
        }
        else
        {
            $dDiscount = round($oSeries->oxvoucherseries__oxdiscount->value / 100 * $dPrice, 2);
        }

        if ($dDiscount > $dPrice)
        {
            $dDiscount = $dPrice;
        }

        return $dDiscount;
    }

    public function getAccessories()
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $rows = $oDb->getAll("select oxid from oxvoucherserieaccessory where oxvoucherserieid=".$oDb->quote($this->oxvouchers__oxvoucherserieid->value));
        $accessories = array();
        foreach ($rows as $row)
        {
            $oAccessory = oxNew("oxvoucherserieaccessory");
            $oAccessory->load($row[0]);
            $accessories[] = $oAccessory;
        }
        return $accessories;
    }

    public function getDiscountType()
    {
        try
        {
            return parent::getDiscountType();
        }
        catch (\Exception $ex)
        {
            $oSeries = $this->getSerie();
            return $oSeries->oxvoucherseries__oxdiscounttype->value;
        }
    }

    protected function isAvailable()
    {
        if ($this->oxvouchers__agrestvalue->value > 0.0001)
            return true;
        return parent::isAvailable();
    }
}