<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;

class Basket extends Basket_parent
{
    /**
     * Modified to respect rest values, will do the default check and then will
     * try again for a rest value
     * @param string $sVoucherId
     */
    public function addVoucher( $sVoucherId )
    {
        // calculating price to check
        // P using prices sum which has discount, not sum of skipped discounts
        $dPrice = 0;
        if ($this->_oDiscountProductsPriceList)
        {
            $dPrice = $this->_oDiscountProductsPriceList->getBruttoSum();
        }

        try
        { // trying to load voucher and apply it

            $oVoucher = oxNew(\OxidEsales\Eshop\Application\Model\Voucher::class);

            if (!$this->_blSkipVouchersAvailabilityChecking)
            {
                try
                {
                    $oVoucher->getVoucherByNr( $sVoucherId, $this->_aVouchers, true);
                }
                catch (\OxidEsales\Eshop\Core\Exception\VoucherException $oEx)
                {
                    $oBasketUser = $this->getBasketUser();
                    if ($oBasketUser)
                    {
                        $sBasketUserId = $oBasketUser->getId();
                    }
                    $oVoucher->getRestVoucherByNr($sVoucherId, $sBasketUserId, $this->_aVouchers, true);
                }

                $oVoucher->checkVoucherAvailability($this->_aVouchers, $dPrice);
                $oVoucher->checkUserAvailability($this->getBasketUser());
                $oVoucher->markAsReserved();
            }
            else
            {
                $oVoucher->load($sVoucherId);
            }

            $accessories = $oVoucher->getAccessories();
            foreach ($accessories as $accessory)
            {
                $oBasketItem = $this->addToBasket($accessory->oxvoucherserieaccessory__oxarticleid->value, $accessory->oxvoucherserieaccessory__oxquantity->value, null, array("details"=>1,"wwvoucher"=>$oVoucher->getId(),"wwvoucheraccessory"=>md5(uniqid())));
                $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
                $oPrice->setPrice($accessory->oxvoucherserieaccessory__oxprice->value);
                $oBasketItem->setPrice($oPrice);
            }
            // saving voucher info
            $this->_aVouchers[$oVoucher->oxvouchers__oxid->value] = $oVoucher->getSimpleVoucher();
        }
        catch (\OxidEsales\Eshop\Core\Exception\VoucherException $oEx)
        {
            // problems adding voucher
            \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay( $oEx, false, true );
        }

        $this->onUpdate();
    }

    /**
     * Calculates voucher discount
     *
     * @return null
     */
    protected function calcVoucherDiscount()
    {
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('bl_showVouchers') && ($this->_oVoucherDiscount === null || ($this->_blUpdateNeeded && !$this->isAdmin())))
        {

            $this->_oVoucherDiscount = $this->getPriceObject();
            $this->_oGiftedVoucherDiscount = $this->getPriceObject();

            // calculating price to apply discount
            $dPrice = $this->_oDiscountProductsPriceList->getSum($this->isCalculationModeNetto()) - $this->_oTotalDiscount->getPrice();

            // recalculating
            if (count($this->_aVouchers) > 0)
            {
                $oLang = \OxidEsales\Eshop\Core\Registry::getLang();
                foreach ($this->_aVouchers as $sVoucherId => $oStdVoucher)
                {
                    $oVoucher = oxNew(\OxidEsales\Eshop\Application\Model\Voucher::class);
                    try
                    { // checking
                        $oVoucher->load($oStdVoucher->sVoucherId);

                        if (!$this->_blSkipVouchersAvailabilityChecking)
                        {
                            $oVoucher->checkBasketVoucherAvailability($this->_aVouchers, $dPrice);
                            $oVoucher->checkUserAvailability($this->getBasketUser());
                        }

                        // assigning real voucher discount value as this is the only place where real value is calculated
                        $dVoucherdiscount = $oVoucher->getDiscountValue($dPrice);

                        if ($dVoucherdiscount > 0 && $oVoucher->oxvouchers__agisvoucher->value != 1)
                        {

                            if ($oVoucher->getDiscountType() == 'absolute')
                            {
                                $dVatPart = ($dPrice - $dVoucherdiscount) / $dPrice * 100;
                            }
                            else
                            {
                                $dVatPart = 100 - $oVoucher->getDiscount();
                            }

                            if (!$this->_aDiscountedVats)
                            {
                                if ($oPriceList = $this->getDiscountProductsPrice())
                                {
                                    $this->_aDiscountedVats = $oPriceList->getVatInfo($this->isCalculationModeNetto());
                                }
                            }

                            // apply discount to vat
                            foreach ($this->_aDiscountedVats as $sKey => $dVat) {
                                $this->_aDiscountedVats[$sKey] = \OxidEsales\Eshop\Core\Price::percent($dVat, $dVatPart);
                            }
                        }

                        if ($oVoucher->oxvouchers__agisvoucher->value == 1)
                        {
                            $this->_oGiftedVoucherDiscount->add($dVoucherdiscount);
                        }

                        // accumulating discount value
                        $this->_oVoucherDiscount->add($dVoucherdiscount);

                        // collecting formatted for preview
                        $oStdVoucher->fVoucherdiscount = $oLang->formatCurrency($dVoucherdiscount, $this->getBasketCurrency());
                        $oStdVoucher->dVoucherdiscount = $dVoucherdiscount;

                        // subtracting voucher discount
                        $dPrice = $dPrice - $dVoucherdiscount;


                    }
                    catch (\OxidEsales\Eshop\Core\Exception\VoucherException $oEx)
                    {
                        // removing voucher on error
                        $oVoucher->unMarkAsReserved();
                        unset($this->_aVouchers[$sVoucherId]);
                        // storing voucher error info
                        \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($oEx, false, true);
                    }
                }
            }
        }

    }


    protected function clearBundles() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        parent::clearBundles();
        if (!\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('wawiusediscountaccessories'))
            return;
        reset($this->_aBasketContents);
        $toremove = [];
        foreach ($this->_aBasketContents as $sItemKey => $oBasketItem)
        {
            $persparam = $oBasketItem->getPersParams();
            if ($persparam && @$persparam["wwdiscount"])
            {
                $toremove[] = $sItemKey;
            }
        }
        foreach ($toremove as $sItemKey)
        {
            $this->removeItem($sItemKey);
        }
    }

    protected function warexoShouldIgnoreBundle($oBasketItem)
    {
        return false;
    }

    protected function warexoShouldAddBundle($accessory, $qty)
    {
        return true;
    }

    protected function addBundles()
    {
        parent::addBundles();
        if (!\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('wawiusediscountaccessories'))
            return;
        //$this->_clearBundles();
        $usedAccessoryProducts = array();
        foreach ($this->_aBasketContents as $key => $oBasketItem)
        {
            if ($this->warexoShouldIgnoreBundle($oBasketItem))
            {
                continue;
            }
            $aDiscounts = \OxidEsales\Eshop\Core\Registry::get(\OxidEsales\Eshop\Application\Model\DiscountList::class)->getBasketItemDiscounts($oBasketItem->getArticle(true), $this, $this->getBasketUser());
            foreach ($aDiscounts as $oDiscount)
            {
                $accessories = $oDiscount->getAccessories();
                foreach ($accessories as $accessory)
                {
                    if ($oDiscount->oxdiscount__oxamount->value > $oBasketItem->getAmount() && !$oDiscount->isGlobalDiscount())
                        continue;
                    if (!$accessory->oxdiscountaccessory__oxarticleid->value)
                        continue;
                    if (!oxNew(\OxidEsales\Eshop\Application\Model\Article::class)->load($accessory->oxdiscountaccessory__oxarticleid->value))
                        continue;

                    $qty = 0;
                    if (!$accessory->oxdiscountaccessory__oxcarttype->value)
                        $qty = $accessory->oxdiscountaccessory__oxquantity->value * $oBasketItem->getAmount();
                    else if (intval($accessory->oxdiscountaccessory__oxcarttype->value) === 2)
                        $qty = $accessory->oxdiscountaccessory__oxquantity->value;
                    else if (intval($accessory->oxdiscountaccessory__oxcarttype->value) === 4)
                        $qty = floor($oBasketItem->getAmount() / $oDiscount->oxdiscount__oxamount->value) * $accessory->oxdiscountaccessory__oxquantity->value;
                    else if (intval($accessory->oxdiscountaccessory__oxcarttype->value) === 5)
                    {
                        if ($usedAccessoryProducts[$accessory->oxdiscountaccessory__oxarticleid->value])
                            continue;
                        $qty = $accessory->oxdiscountaccessory__oxquantity->value;
                        $usedAccessoryProducts[$accessory->oxdiscountaccessory__oxarticleid->value] = 1;
                    }
                    else
                        continue;
                    try
                    {
                        $oBasketUser = $this->getBasketUser();
                        $groups = $accessory->oxdiscountaccessory__oxgroups->rawValue;
                        $excludedGroups = $accessory->oxdiscountaccessory__oxexcludedgroups->rawValue;
                        if ($groups && count((array)json_decode($groups)) > 0)
                        {
                            if (!$oBasketUser)
                                continue;
                            $groups = json_decode($groups);
                            $inGroup = false;
                            foreach ($groups as $group)
                            {
                                $groupId = null;
                                if ($group->foreignId)
                                    $groupId = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getOne("select oxid from oxgroups where oxid=".\OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($group->foreignId));
                                if (!$groupId && $group->id)
                                    $groupId = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getOne("select oxid from oxgroups where wwforeignid=".\OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($group->id));
                                if ($groupId && $oBasketUser->inGroup($groupId))
                                {
                                    $inGroup = true;
                                    break;
                                }
                            }
                            if (!$inGroup)
                                continue;
                        }
                        else
                            $inGroup = true;
                        if ($inGroup && $excludedGroups && count((array)json_decode($excludedGroups)) > 0)
                        {
                            $excludedGroups = json_decode($excludedGroups);
                            $inGroup = false;
                            foreach ($excludedGroups as $group)
                            {
                                $groupId = null;
                                if ($group->foreignId)
                                    $groupId = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getOne("select oxid from oxgroups where oxid=".\OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($group->foreignId));
                                if (!$groupId && $group->id)
                                    $groupId = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getOne("select oxid from oxgroups where wwforeignid=".\OxidEsales\Eshop\Core\DatabaseProvider::getDb()->quote($group->id));
                                if ($groupId && $oBasketUser && $oBasketUser->inGroup($groupId))
                                {
                                    $inGroup = true;
                                    break;
                                }
                            }
                            if ($inGroup)
                                continue;
                        }
                        if (!$this->warexoShouldAddBundle($accessory, $qty))
                            continue;
                        $oBasketItem = $this->addToBasket($accessory->oxdiscountaccessory__oxarticleid->value, $qty, null, array("details"=>1,
                            "wwdiscount"=>$oDiscount->getId(),
                            "wwaccessoryprice"=>$accessory->oxdiscountaccessory__oxprice->value,
                            "wwpricehash" => md5($accessory->oxdiscountaccessory__oxprice->value."|".$oDiscount->getId()."|jLzethb"),
                            "wwdiscountaccessory"=>md5(uniqid())));
                        $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
                        $oPrice->setPrice($accessory->oxdiscountaccessory__oxprice->value);
                        $oBasketItem->setPrice($oPrice);
                    }
                    catch (\Exception $ex)
                    {
                        \OxidEsales\Eshop\Core\Registry::getUtilsView()->addErrorToDisplay($ex, false, true );
                    }

                }
            }
        }
    }



    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     */
    public function getDiscountedProductsSum()
    {
        $dPrice = parent::getDiscountedProductsSum();

        if ($oGiftedVoucherPrice = $this->getGiftedVoucherDiscount())
        {
            $dPrice += $oGiftedVoucherPrice->getPrice();
        }
        return $dPrice;
    }

    /**
     * Returns ( current basket products sum - total discount - voucher discount )
     *
     * @return double
     */
    public function getDiscountedProductsBruttoPrice()
    {
        $dPrice = parent::getDiscountedProductsBruttoPrice();

        if ($oGiftedVoucherPrice = $this->getGiftedVoucherDiscount() )
        {
            $dPrice -= $oGiftedVoucherPrice->getBruttoPrice();
            if ( $oVoucherPrice = $this->getVoucherDiscount() ) {
                $dPrice += $oVoucherPrice->getBruttoPrice();
            }
        }

        return $dPrice;
    }

    public function getGiftedVoucherDiscount()
    {
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('bl_showVouchers'))
        {
            return $this->_oGiftedVoucherDiscount;
        }
        return null;
    }

    public function removeItem($sItemKey)
    {
        $toremove = array();
        $oBasketItem = $this->_aBasketContents[$sItemKey];
        $voucherIdToRemove = null;
        if ($oBasketItem)
        {
            $persparam = $oBasketItem->getPersParams();
            if ($persparam && @$persparam["wwvoucher"])
            {
                $voucherIdToRemove = $persparam["wwvoucher"];
                foreach ($this->getContents() as $key => $val)
                {
                    $persparam2 = $val->getPersParams();
                    if ($persparam2 && @$persparam2["wwvoucher"] == $persparam["wwvoucher"])
                        $toremove[] = $key;

                }
            }
        }
        parent::removeItem($sItemKey);
        foreach ($toremove as $key)
            parent::removeItem($key);
        if ($voucherIdToRemove)
            $this->removeVoucher($voucherIdToRemove);
    }
}