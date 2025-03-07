<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\TableViewNameGenerator;

class ArticleList extends ArticleList_parent
{
    protected function getCategorySelect($sFields, $sCatId, $aSessionFilter)
    {
        $oDb = DatabaseProvider::getDb();

        if (!$this->_sCustomSorting && !$oDb->getOne("select oxid from oxobject2category where oxcatnid=".$oDb->quote($sCatId)." and oxpos > 0 limit 0,1"))
        {
            $tableViewNameGenerator = oxNew(TableViewNameGenerator::class);
            $sArticleTable = $tableViewNameGenerator->getViewName('oxarticles');
            $this->_sCustomSorting = "$sArticleTable.oxsort";
        }
        return parent::getCategorySelect($sFields, $sCatId, $aSessionFilter);
    }
}