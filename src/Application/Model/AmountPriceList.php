<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

class AmountPriceList extends AmountPriceList_parent
{
    protected function loadFromDb()
    {
        $sArticleId = $this->getArticle()->getId();
        $oArticle = $this->getArticle();

        if (!$this->isAdmin() && Registry::getConfig()->getConfigParam('blVariantInheritAmountPrice') && $this->getArticle()->getParentId())
        {
            $sArticleId = $this->getArticle()->getParentId();
            $oArticle = $oArticle->getParentArticle();
        }

        $oUser = $oArticle->getArticleUser();

        if ($oUser)
        {
            $oDb = DatabaseProvider::getDb();

            $sGroupSelect = ' AND ( oxgroupsid = "" or oxgroupsid IS NULL or oxgroupsid IN ('
                . ' SELECT o2g.oxgroupsid FROM oxobject2group o2g '
                . ' WHERE o2g.oxshopid = '.$oDb->quote( Registry::getConfig()->getShopId() )
                . ' AND o2g.oxobjectid = '.$oDb->quote( $oUser->getId() ) . ') ) ';
        }
        else
        {
            $sGroupSelect = ' AND ( oxgroupsid = "" or oxgroupsid IS NULL ) ';
        }

        if (Registry::getConfig()->getConfigParam('blMallInterchangeArticles'))
        {
            $sShopSelect = '1';
        }
        else
        {
            $sShopSelect = " `oxshopid` = " . DatabaseProvider::getDb()->quote(Registry::getConfig()->getShopId()) . " ";
        }

        $sSql = "SELECT * FROM `oxprice2article` WHERE `oxartid` = " . DatabaseProvider::getDb()->quote($sArticleId) . " AND $sShopSelect $sGroupSelect ORDER BY oxgroupsid DESC, `oxamount` ASC ";

        $aData = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC)->getAll($sSql);
        if ($oUser)
        {
            $newData = array();
            $hasGroupsId = false;
            foreach ($aData as $item)
            {
                if ($item["OXGROUPSID"])
                {
                    $newData[] = $item;
                    $hasGroupsId = true;
                }
                else if ($hasGroupsId)
                    break;
                else
                    $newData[] = $item;
            }
            $aData = $newData;
        }
        return $aData;
    }
}