<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Application\Model\Discount;
use OxidEsales\Eshop\Application\Model\Voucher;
use OxidEsales\Eshop\Application\Model\VatSelector;
use OxidEsales\Eshop\Application\Model\CountryList;
use Warexo\Core\SettingsHelper;

class Article extends Article_parent
{
    protected $_dGroupPrice = null;
    protected $_dVarMinGroupPrice = null;

    public $wawiStockUpdate;

    protected function getGroupPrice()
    {
        $oUser = $this->getArticleUser();

        if ($oUser)
        {
            if ($this->_dGroupPrice === null)
            {
                $oDb = DatabaseProvider::getDb();
                $field = 'oxprice';
                if (Registry::getConfig()->getConfigParam('wawiUseSortFieldForCustomerPrices'))
                    $field = 'oxsort';
                $sSelect =  'SELECT g2p.oxprice FROM oxgroup2price g2p '
                    . ' WHERE g2p.oxarticleid = '.$oDb->quote( $this->getId() ).' AND g2p.oxgroupid IN ('
                    . ' SELECT o2g.oxgroupsid FROM oxobject2group o2g '
                    . ' WHERE o2g.oxshopid = '.$oDb->quote( Registry::getConfig()->getShopId() )
                    . ' AND o2g.oxobjectid = '.$oDb->quote( $oUser->getId() )
                    . ') ORDER BY '.$field.' ASC LIMIT 0,1';

                $dPrice = $oDb->GetOne($sSelect);

                if ($dPrice)
                {
                    $this->_dGroupPrice = $dPrice;
                    return $dPrice;
                }
                else
                {
                    $this->_dGroupPrice = parent::getGroupPrice();
                }
            }else{
                return $this->_dGroupPrice;
            }

        }

        return parent::getGroupPrice();
    }

    protected function getVarMinGroupPrice()
    {
        $oUser = $this->getArticleUser();

        if ($oUser)
        {
            if ($this->_dVarMinGroupPrice === null)
            {
                $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();

                $sSelect =  'SELECT MIN(g2p.oxprice) FROM oxgroup2price g2p '
                    . 'join oxarticles vrnt on g2p.oxarticleid=vrnt.oxid'
                    . ' WHERE vrnt.oxactive=1 AND (vrnt.oxparentid = '.$oDb->quote($this->getId()).' OR g2p.oxarticleid='.$oDb->quote( $this->getId() ).') AND g2p.oxgroupid IN ('
                    . ' SELECT o2g.oxgroupsid FROM oxobject2group o2g '
                    . ' WHERE o2g.oxshopid = '.$oDb->quote(Registry::getConfig()->getShopId())
                    . ' AND o2g.oxobjectid = '.$oDb->quote($oUser->getId())
                    . ')';

                $dPrice = $oDb->GetOne($sSelect);

                if ($dPrice)
                {
                    $this->_dVarMinGroupPrice = $dPrice;
                    return $dPrice;
                }
                else
                {
                    $this->_dVarMinGroupPrice =  parent::getGroupPrice();
                }
            }
            else
            {
                return $this->_dVarMinGroupPrice;
            }

        }
        return parent::getVarMinPrice();
    }

    /**
     * Checks if articles has amount price
     *
     * @return bool
     */
    public function hasAmountPrice()
    {
        if (self::$_blHasAmountPrice === null)
        {
            $oUser = $this->getArticleUser();

            if ($oUser)
            {
                $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
                $sGroupSelect = " WHERE oxgroupsid = '' OR oxgroupsid IS NULL or oxgroupsid IN ( SELECT oxgroupsid FROM oxobject2group WHERE oxobjectid = " . $oDb->quote( $oUser->getId() ) . " ) ";
            }
            else
            {
                $sGroupSelect = " WHERE oxgroupsid = '' OR oxgroupsid IS NULL ";
            }

            self::$_blHasAmountPrice = false;

            $oDb = $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sQ = "SELECT 1 FROM `oxprice2article` $sGroupSelect LIMIT 1";

            if ($oDb->getOne($sQ))
            {
                self::$_blHasAmountPrice = true;
            }
        }

        return self::$_blHasAmountPrice;
    }

    public function getVarMinPrice()
    {
        $dVarMinPrice = parent::getVarMinPrice();
        $dGroupPrice = $this->getVarMinGroupPrice();

        if ($dGroupPrice > 0 && ($dGroupPrice < $dVarMinPrice || !$dVarMinPrice))
        {
            $this->_dVarMinPrice = $dGroupPrice;
        }
        $oPrice = $this->getPriceObject();
        $oPrice->setPrice($this->_dVarMinPrice);
        return $oPrice;
    }

    /**
     * Check if this product was assigned to a special group as well
     */
    public function getSqlActiveSnippet($blForceCoreTable = null )
    {
        $conf = Registry::getConfig();
        $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
        $sQ = parent::getSqlActiveSnippet( $blForceCoreTable );
        if (!$this->isAdmin() && (!$conf->getConfigParam('wawiNotHideArticleWithGroups') || $this->wawiHideArticleWithGroups) && !$this->wawiStockUpdate)
        {
            $sTable = $this->getViewName( $blForceCoreTable );
            $oUser  = $this->getArticleUser();
            if ($this->wawiHideArticleWithGroups)
            {
                if ($oUser)
                    $sQ .= " and ( ( SELECT COUNT(*) cnt FROM oxarticle2group WHERE oxobjectid = $sTable.oxid ) = 0 "
                        . "OR ( $sTable.oxid IN ( SELECT oxobjectid FROM oxarticle2group WHERE oxgroupsid IN ( SELECT oxgroupsid FROM oxobject2group WHERE oxobjectid = " . $oDb->quote( $oUser->getId() ). " ) ) ) ) ";
                else
                    $sQ .= " and ( ( SELECT COUNT(*) cnt FROM oxarticle2group WHERE oxobjectid = $sTable.oxid ) = 0 ) ";
            }
            else
            {
                if ($oUser)
                    $sQ .= " and ( ( SELECT COUNT(*) cnt FROM oxarticle2group JOIN oxgroups on oxgroups.oxid = oxarticle2group.oxgroupsid WHERE oxobjectid = $sTable.oxid and oxgroups.wwdisplayproductsforother=0) = 0 OR "
                        . "( $sTable.oxid IN ( SELECT oxobjectid FROM oxarticle2group JOIN oxgroups on oxgroups.oxid = oxarticle2group.oxgroupsid WHERE oxgroups.wwdisplayproductsforother=1 OR oxgroupsid IN ( SELECT oxgroupsid FROM oxobject2group WHERE oxobjectid = " . $oDb->quote( $oUser->getId() ). " ) ) ) ) ";
                else
                    $sQ .= " and ( ( SELECT COUNT(*) cnt FROM oxarticle2group JOIN oxgroups on oxgroups.oxid = oxarticle2group.oxgroupsid WHERE oxobjectid = $sTable.oxid and oxgroups.wwdisplayproductsforother=0) = 0 ) ";
            }
        }
        return $sQ;

    }

    protected $wrxVisible;

    /**
     * group assign check for details page etc.
     */
    public function isVisible()
    {
        $blIsVisible = parent::isVisible();
        if (Registry::get(\OxidEsales\Eshop\Core\Request::class)->getRequestParameter("wrxpreviewtoken"))
        {
            if ($this->wrxVisible)
                return true;
            $id = DatabaseProvider::getDb()->getOne("select id from wawitokens where id=".DatabaseProvider::getDb()->quote(Registry::get(\OxidEsales\Eshop\Core\Request::class)->getRequestParameter("wrxpreviewtoken"))." and expired > now()");
            if ($id)
            {
                $this->wrxVisible = true;
                return true;
            }
        }
        if($blIsVisible && !$this->isAdmin() && !Registry::getConfig()->getConfigParam('wawiNotOverrideIsVisible')
            && !Registry::getConfig()->getConfigParam('wawiNotHideArticleWithGroups'))
        {
            $sTable = $this->getViewName( $blForceCoreTable );
            $sOxid = DatabaseProvider::getDb()->getOne( "SELECT oxid FROM $sTable WHERE oxid = ".DatabaseProvider::getDb()->quote($this->getId())." and " . $this->getSqlActiveSnippet());
            if(!$sOxid)
            {
                if ($this->oxarticles__oxhidden->value)
                    $sOxid = DatabaseProvider::getDb()->getOne("SELECT oxid FROM $sTable WHERE oxid = ".DatabaseProvider::getDb()->quote($this->getId()));
                if(!$sOxid)
                    $blIsVisible = false;

            }
        }

        return $blIsVisible;
    }

    protected function getLicensePrice($dAmount)
    {
        $params = $this->getPersParams();
        $licenseprice = @$params["updatelicenseprice"];

        if ($licenseprice)
        {
            if (md5($licenseprice.'|'.$params['previouslicense'].'|vtshZkhj1') == $params["licensehash"])
            {
                $price = parent::getPrice($dAmount);
                $price->setPrice($licenseprice);
                return $price;
            }
            else
                return null;
        }
        else
        {
            return null;
        }
    }

    protected function getWarexoSpecialPrice($dAmount)
    {
        $params = $this->getPersParams();
        $specialprice = @$params["warexospecialprice"];

        if ($specialprice)
        {
            if (md5($specialprice.'|vtshZkhj1') == $params["warexospecialpricehash"])
            {
                $price = parent::getPrice($dAmount);
                $price->setPrice($specialprice);
                return $price;
            }
            else
                return null;
        }
        else
        {
            return null;
        }
    }

    protected function getDiscountAccessoryPrice($dAmount, $oBasket=null)
    {
        $params = $this->getPersParams();
        $discountaccessory = @$params["wwdiscountaccessory"];

        if ($discountaccessory && @$params["wwdiscount"])
        {
            if ($oBasket)
            {
                $oDiscount = oxNew(Discount::class);
                if ($oDiscount->load($params["wwdiscount"]) && md5($params["wwaccessoryprice"].'|'.$oDiscount->getId()."|jLzethb") == $params["wwpricehash"])
                {
                    $price = parent::getPrice($dAmount);
                    $price->setPrice(floatval($params["wwaccessoryprice"]));
                    return $price;
                }
            }
            return null;
        }
        else
        {

            return null;
        }
    }

    protected function getVoucherAccessoryPrice($dAmount, $oBasket=null)
    {
        $params = $this->getPersParams();
        $voucheraccessory = @$params["wwvoucheraccessory"];

        if ($voucheraccessory)
        {
            if ($oBasket)
                foreach ($oBasket->getVouchers() as $voucherObj)
                {
                    $oVoucher = oxNew(Voucher::class);
                    $oVoucher->load($voucherObj->sVoucherId);
                    foreach ($oVoucher->getAccessories() as $oVoucherSerieAccessory)
                    {
                        if ($oVoucherSerieAccessory->oxvoucherserieaccessory__oxarticleid->value == $this->getId() && $dAmount == $oVoucherSerieAccessory->oxvoucherserieaccessory__oxquantity->value)
                        {
                            $price = parent::getPrice($dAmount);
                            $price->setPrice($oVoucherSerieAccessory->oxvoucherserieaccessory__oxprice->value);
                            return $price;
                        }
                    }
                }
            return null;
        }
        else
        {

            return null;
        }
    }

    protected $_aPersistParam = [];

    public function getPersParams()
    {
        return $this->_aPersistParam;
    }

    public function setPersParam($aPersParam)
    {
        $this->_aPersistParam = $aPersParam;
    }

    public function getBasketPrice($dAmount, $aSelList, $oBasket)
    {
        $licensePrice = $this->getLicensePrice($dAmount);
        $specialPrice = $this->getWarexoSpecialPrice($dAmount);
        $voucherAccessoryPrice = $this->getVoucherAccessoryPrice($dAmount, $oBasket);
        $discountAccessoryPrice = $this->getDiscountAccessoryPrice($dAmount, $oBasket);
        if ($licensePrice !== null)
        {
            return $licensePrice;
        }
        if ($specialPrice !== null)
            return $specialPrice;
        else if ($voucherAccessoryPrice !== null)
        {
            return $voucherAccessoryPrice;
        }
        else if ($discountAccessoryPrice !== null)
            return $discountAccessoryPrice;
        else
        {
            $price = parent::getBasketPrice( $dAmount, $aSelList, $oBasket );
            $oConf = Registry::getConfig();
            if (SettingsHelper::getBool('warexo', 'wawiuseoss') && SettingsHelper::getBool('warexo', 'wawiusenettofoross') && date('Y-m-d') >= '2021-07-01' &&
                $price && !$price->isNettoMode())
            {
                $vatselector = Registry::get(VatSelector::class);
                $vatselector->wawiDisableOss = true;
                $vat = $vatselector->getArticleVat($this);
                $vatselector->wawiDisableOss = false;
                $userVat = $vatselector->getArticleVat($this);
                if ($userVat > 0.001 && abs($userVat - 7) > 0.001 && abs($userVat - 19) > 0.001)
                {
                    $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
                    $oPrice->setPrice($price->getBruttoPrice() / (1+$vat/100) * (1 + $vatselector->getArticleVat($this)/100));
                    $oPrice->setVat($userVat);
                    $price = $oPrice;
                }

            }
            return $price;
        }
    }

    public function getOSSPriceTable()
    {
        $oDb = DatabaseProvider::getDb();
        $oCountryList = oxNew(CountryList::class);
        $oCountryList->selectString("select * from oxcountry where oxactive=1 and oxvatstatus=1");
        $arr = array();
        foreach ($oCountryList as $oCountry)
        {
            $obj = new \stdClass();
            $obj->country = $oCountry;
            $obj->countryname = $oCountry->oxcountry__oxtitle->value;
            $obj->price = number_format($this->calcOSSPriceForEUCountry($oCountry->oxcountry__oxisoalpha2->value), 2, ",", "");
            $arr[] = $obj;
        }
        return $arr;
    }

    public function calcOSSPriceForEUCountry($countryCode)
    {
        $vatselector = Registry::get(VatSelector::class);
        $this->wawiDisableOss = true;
        $price = $this->getPrice();
        if ($this->isParentNotBuyable())
            $price = $this->getVarMinPrice();
        $vatselector->wawiDisableOss = true;
        $vat = $vatselector->getArticleVat($this);
        $this->wawiDisableOss = false;
        $vatselector->wawiDisableOss = false;
        $countryVat = $vatselector->getOSSVatByCountry($countryCode, abs($vat - 7) < 0.0001);
        return round($price->getBruttoPrice() / (1+$vat/100) * (1 + $countryVat/100), 2);
    }

    public function getPrice($dAmount = 1)
    {
        $licensePrice = $this->getLicensePrice($dAmount);
        $specialPrice = $this->getWarexoSpecialPrice($dAmount);
        $voucherAccessoryPrice = $this->getVoucherAccessoryPrice($dAmount);
        if($licensePrice !== null)
        {
            return $licensePrice;
        }
        else if ($specialPrice !== null)
        {
            return $specialPrice;
        }
        else if ($voucherAccessoryPrice !== null)
        {
            return $voucherAccessoryPrice;
        }
        else
        {
            $price = parent::getPrice( $dAmount );
            $oConf = Registry::getConfig();
            if (SettingsHelper::getBool('warexo', 'wawiuseoss') && SettingsHelper::getBool('warexo', 'wawiusenettofoross') && date('Y-m-d') >= '2021-07-01' &&
                $price && !$price->isNettoMode() && !$this->wawiDisableOss)
            {
                $vatselector = Registry::get(VatSelector::class);;
                $vatselector->wawiDisableOss = true;
                $vat = $vatselector->getArticleVat($this);
                $vatselector->wawiDisableOss = false;
                $userVat = $vatselector->getArticleVat($this);
                if ($userVat > 0.001 && abs($userVat - 7) > 0.001 && abs($userVat - 19) > 0.001)
                {
                    $oPrice = oxNew(\OxidEsales\Eshop\Core\Price::class);
                    $oPrice->setPrice($price->getBruttoPrice() / (1+$vat/100) * (1 + $vatselector->getArticleVat($this)/100));
                    $oPrice->setVat($userVat);
                    $price = $oPrice;
                }

            }
            return $price;
        }

    }

    public function isNotBuyable()
    {
        $res = parent::isNotBuyable();
        if (!$res)
        {
            $sTable = $this->getViewName($blForceCoreTable);
            $this->wawiHideArticleWithGroups = true;
            $sOxid = DatabaseProvider::getDb()->getOne( "SELECT oxid FROM $sTable WHERE oxid = ".DatabaseProvider::getDb()->quote($this->getId())." and " . $this->getSqlActiveSnippet() );
            $this->wawiHideArticleWithGroups = false;
            if (!$sOxid)
                return true;
        }
        return $res;
    }

    public function agIsPriceVisible()
    {
        //if ($this->getConfig()->getConfigParam( 'wawiNotHideArticleWithGroups' ))
        {
            $sTable = $this->getViewName( null );
            $oUser  = $this->getArticleUser();
            $oDb = DatabaseProvider::getDb();
            if (!$oDb->getOne("SELECT COUNT(*) cnt FROM oxarticle2group WHERE oxobjectid = ".$oDb->quote($this->getId())))
            {
                if ($this->oxarticles__oxparentid->value)
                {
                    if (!$oDb->getOne("SELECT COUNT(*) cnt FROM oxarticle2group WHERE oxobjectid = ".$oDb->quote($this->oxarticles__oxparentid->value)))
                        return true;
                }
                else
                    return true;
            }

            if ($oUser)
            {
                if ($this->oxarticles__oxparentid->value)
                    return $oDb->getOne("select count(*) FROM oxarticle2group WHERE (oxobjectid = ".$oDb->quote($this->getId())." or oxobjectid = ".$oDb->quote($this->oxarticles__oxparentid->value).") and oxgroupsid IN ( SELECT oxgroupsid FROM oxobject2group WHERE oxobjectid = " . $oDb->quote( $oUser->getId() ). ") ") > 0;
                else
                    return $oDb->getOne("select count(*) FROM oxarticle2group WHERE oxobjectid = ".$oDb->quote($this->getId())." and oxgroupsid IN ( SELECT oxgroupsid FROM oxobject2group WHERE oxobjectid = " . $oDb->quote( $oUser->getId() ). ") ") > 0;
            }
            return false;
        }
        return true;
    }
}