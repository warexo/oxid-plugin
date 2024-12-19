<?php

namespace Warexo\Application\Model;

class VatSelector extends VatSelector_parent
{
    protected $taxValues = array(
        'BE' => 21,
        'BG' => 20,
        'AT' => 20,
        'DK' => 25,
        'PL' => 23,
        'CZ' => 21,
        'FR' => 20,
        'HU' => 27,
        'GR' => 24,
        'IT' => 22,
        'ES' => 21,
        'NL' => 21,
        'PT' => 23,
        'DE' => 19,
        'RO' => 19,
        'IE' => 23,
        'LV' => 21,
        'SI' => 22,
        'SK' => 20,
        'MT' => 18,
        'LU' => 17,
        'EE' => 22,
        'CY' => 19,
        'HR' => 25,
        'FI' => 25.5,
        'LT' => 21,
        'SE' => 25,
    );

    protected $reducedTaxValues = array(
        'AT' => 10,
        'CZ' => 12,
        'PL' => 5,
        'FR' => 5.5,
        'IT' => 5,
        'CY' => 5,
        'BG' => 9,
        'BE' => 6,
        'DE' => 7,
        'EE' => 9,
        'GR' => 6,
        'ES' => 10,
        'FI' => 10,
        'HR' => 5,
        'HU' => 5,
        'IE' => 9,
        'LT' => 5,
        'LU' => 7,
        'LV' => 12,
        'MT' => 5,
        'NL' => 9,
        'PT' => 6,
        'RO' => 5,
        'SE' => 6,
        'SI' => 9.5,
        'SK' => 10
    );

    public $wawiDisableOss;

    public function getOSSVatByCountry($countryCode, $reduced)
    {
        if (!$reduced)
            return $this->taxValues[$countryCode];
        else
            return $this->reducedTaxValues[$countryCode];
    }

    public function getArticleVat(\OxidEsales\Eshop\Application\Model\Article $oArticle)
    {
        $vat = parent::getArticleVat($oArticle);
        $oConf = agConfig::getInstance();
        if ($oUser = agSession::getInstance()->getBasket()->getBasketUser())
        {
            $oAddress = $oUser->getSelectedAddress();
            if ($oAddress && $oAddress->oxaddress__oxzip->value == "27498")
            {
                $countryId = $oAddress->oxaddress__oxcountryid->value;
                $oCountry = oxNew("oxcountry");
                $oCountry->load($countryId);
                if ($oCountry->oxcountry__oxisoalpha2->value == "DE")
                    return 0;
            }
            else if (!$oAddress && $oUser->oxuser__oxzip->value == "27498")
            {
                $countryId = $oUser->oxuser__oxcountryid->value;
                $oCountry = oxNew("oxcountry");
                $oCountry->load($countryId);
                if ($oCountry->oxcountry__oxisoalpha2->value == "DE")
                    return 0;
            }
        }
        if ($this->wawiDisableOss || !$oConf->getShopConfVar('wawiuseoss') || date('Y-m-d') < '2021-07-01')
            return $vat;
        if ($oUser = agSession::getInstance()->getBasket()->getBasketUser())
        {
            if ($this->getUserVat($oUser) === 0)
                return $vat;
            if ($vat > 0)
            {
                if ($vat == $oConf->getShopConfVar('dDefaultVAT'))
                {
                    if (abs($vat - 7) < 0.001)
                        $aVatRates = $this->reducedTaxValues;
                    else
                        $aVatRates = $this->taxValues;
                }
                else
                {
                    if (abs($vat - 19) < 0.001)
                        $aVatRates = $this->taxValues;
                    else
                        $aVatRates = $this->reducedTaxValues;
                }
                $countryId = $this->getVatCountry($oUser);
                $oCountry = oxNew("oxcountry");
                $oCountry->load($countryId);
                if (isset($aVatRates[$oCountry->oxcountry__oxisoalpha2->value]))
                    $vat = $aVatRates[$oCountry->oxcountry__oxisoalpha2->value];
            }
        }

        return $vat;
    }

    protected function getForeignCountryUserVat(\OxidEsales\Eshop\Application\Model\User $oUser, \OxidEsales\Eshop\Application\Model\Country $oCountry)
    {
        if ($oUser->oxuser__oxustid->value && strpos($oUser->oxuser__oxustid->value, 'DE') !== false)
        {
            $oShop = agConfig::getInstance()->getActiveShop();
            if ($oShop->oxshops__oxcountry->value == "Deutschland")
                return false;
        }
        return parent::getForeignCountryUserVat($oUser, $oCountry);
    }
}