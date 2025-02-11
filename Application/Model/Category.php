<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;
class Category extends Category_parent
{
    protected $userGroupCategories;
    public $wrxNotCheckShowEmpty;

    public function getIsVisible()
    {
        $oConf = \OxidEsales\Eshop\Core\Registry::getConfig();
        if ($oConf->getConfigParam('wawiIgnoreCategoryGroups'))
            return parent::getIsVisible();
        $dontShowEmpty = $oConf->getShopConfVar('blDontShowEmptyCategories');
        if (!$dontShowEmpty)
        {
            $blIsVisible = parent::getIsVisible();
            if (!$blIsVisible)
                return $blIsVisible;
        }
        else
            $blIsVisible = true;
        if ($blIsVisible && !$this->isAdmin())
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oUser = $this->getUser();
            if ($this->userGroupCategories !== null && @$this->userGroupCategories[$this->getId()])
            {
                if ($dontShowEmpty)
                    return parent::getIsVisible();
                return true;
            }
            if ($oDb->getOne("select count(*) from oxcategory2group where oxobjectid=".$oDb->quote($this->getId())))
            {
                if (!$oUser)
                    return false;
                if ($this->userGroupCategories !== null)
                {
                    if ($dontShowEmpty)
                        return @$this->userGroupCategories[$this->getId()] && parent::getIsVisible();
                    return $this->userGroupCategories[$this->getId()];
                }
                $groupsarr = array();
                foreach ($oUser->getUserGroups() as $oGroup)
                {
                    $groupsarr[] = $oDb->quote($oGroup->getId());
                }
                if (count($groupsarr) == 0)
                    return false;
                $this->userGroupCategories = array();
                $rows = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->getAll("select OXOBJECTID from oxcategory2group where oxgroupsid in (".implode(",",$groupsarr).")");
                foreach ($rows as $row)
                    $this->userGroupCategories[$row[0]] = true;
                if (@!$this->userGroupCategories[$this->getId()])
                    return false;
            }

        }
        if ($dontShowEmpty && !$this->wrxNotCheckShowEmpty)
            return parent::getIsVisible();
        return $blIsVisible;
    }
}