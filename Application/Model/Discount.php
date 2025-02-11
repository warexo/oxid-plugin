<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;

class Discount extends Discount_parent
{
    public function getAccessories()
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $rows = $oDb->getAll("select oxid from oxdiscountaccessory where oxdiscountid=".$oDb->quote($this->oxdiscount__oxid->value));
        $accessories = array();
        foreach ($rows as $row)
        {
            $oAccessory = oxNew("oxdiscountaccessory");
            $oAccessory->load($row[0]);
            $accessories[] = $oAccessory;
        }
        return $accessories;
    }

    public function isForBasketItem($oArticle)
    {
        if (\OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('wawiusediscountaccessories') && count($this->getAccessories()) > 0)
        {
            // check if this article is assigned
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sQ = "select 1 from oxobject2discount 
            where oxdiscountid = :oxdiscountid and oxtype = :oxtype ";
            $sQ .= $this->_getProductCheckQuery($oArticle);
            $params = [
                ':oxdiscountid' => $this->oxdiscount__oxid->value,
                ':oxtype' => 'oxarticles'
            ];

            if (!($blOk = (bool)$oDb->getOne($sQ, $params))) {
                // checking article category
                $blOk = $this->_checkForArticleCategories($oArticle);
            }
            return $blOk;
        }
        return parent::isForBasketItem($oArticle);

    }
}