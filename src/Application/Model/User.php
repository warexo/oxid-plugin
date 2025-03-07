<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;

class User extends User_parent
{
    protected $_aSkipSaveFields = array('oxtimestamp');
    protected $wwuserdeleted;
    protected $wwSavedFields = null;

    public function delete($sOXID = NULL)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }
        if (!$sOXID) {
            return false;
        }
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        if ($this->oxuser__oxid->value)
            $this->load($sOXID);
        $sOXIDQuoted = $oDb->quote($sOXID);
        // deleting stored payment, address, group dependencies, remarks info
        $rs = $oDb->execute("delete from oxaddress where oxaddress.oxuserid = {$sOXIDQuoted}");
        $rs = $oDb->execute("delete from oxobject2group where oxobject2group.oxobjectid = {$sOXIDQuoted}");

        // deleting notice/wish lists
        $rs = $oDb->execute("delete oxuserbasketitems.* from oxuserbasketitems, oxuserbaskets where oxuserbasketitems.oxbasketid = oxuserbaskets.oxid and oxuserid = {$sOXIDQuoted}");
        $rs = $oDb->execute("delete from oxuserbaskets where oxuserid = {$sOXIDQuoted}");

        // deleting newsletter subscription
        $rs = $oDb->execute("delete from oxnewssubscribed where oxuserid = {$sOXIDQuoted}");

        // delivery and delivery sets
        $rs = $oDb->execute("delete from oxobject2delivery where oxobjectid = {$sOXIDQuoted}");

        // discounts
        $rs = $oDb->execute("delete from oxobject2discount where oxobjectid = {$sOXIDQuoted}");


        // and leaving all order related information
        $rs = $oDb->execute("delete from oxremark where oxparentid = {$sOXIDQuoted} and oxtype !='o'");
        $fields = array(
            'oxuser__oxcompany',
            'oxuser__oxfname',
            'oxuser__oxlname',
            'oxuser__oxaddinfo',
            'oxuser__oxstreet',
            'oxuser__oxstreetnr',
            'oxuser__oxzip',
            'oxuser__oxcity',
            'oxuser__oxcountryid',
            'oxuser__oxfon',
            'oxuser__oxfax',
            'oxuser__oxpassword',
            'oxuser__oxpasssalt',
            'oxuser__oxustid',
            'oxuser__oxactive',
            'oxuser__oxprivfon',
            'oxuser__oxurl',
            'oxuser__oxusername',
            'oxuser__wwdeleted'
        );
        foreach ($fields as $field)
            $this->wwSavedFields[$field] = $this->{$field}->value;
        $this->oxuser__oxcompany = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxfname = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxlname = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxaddinfo = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxstreet = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxstreetnr = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxzip = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxcity = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxcountryid = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxfon = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxfax = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxpassword = new \OxidEsales\Eshop\Core\Field(uniqid());
        $this->oxuser__oxustid = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxactive = new \OxidEsales\Eshop\Core\Field(0);
        $this->oxuser__oxprivfon = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxurl = new \OxidEsales\Eshop\Core\Field('-');
        $this->oxuser__oxusername = new \OxidEsales\Eshop\Core\Field('~deleted@@'.uniqid());
        $this->oxuser__wwdeleted = new \OxidEsales\Eshop\Core\Field(1);
        $this->save();
        $this->wwuserdeleted = true;
        return true;
    }

    protected function _dbLogin(string $userName, $password, $shopId)
    {
        parent::_dbLogin($userName, $password, $shopId);
        if (!$this->oxuser__oxid->value)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sUserSelect = "oxuser.oxusername = " . $oDb->quote($sUser);
            $sSelect = "select oxid, wwforeignpassword from oxuser where oxuser.oxactive = 1 and {$sUserSelect}";
            $row = $oDb->getRow($sSelect);
            if ($row && @$row[0] && strpos($row[1],"$") === 0)
            {
                if (password_verify($sPassword, $row[1]))
                {
                    $this->load($row[0]);
                }
            }
        }
    }

    public function isSamePassword($sNewPass)
    {
        $blResult = parent::isSamePassword($sNewPass);
        if (!$blResult && $this->oxuser__wwforeignpassword->value && password_verify($sNewPass, $this->oxuser__wwforeignpassword->value))
        {
            $blResult = true;
        }
        return $blResult;
    }

    public function save()
    {
        if ($this->wwuserdeleted)
        {
            $this->setId('');
            foreach ($this->wwSavedFields as $field=>$value)
                $this->{$field} = new \OxidEsales\Eshop\Core\Field($value);
        }
        $res = parent::save();
        if ($this->wwuserdeleted)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $this->oxuser__oxrights = new \OxidEsales\Eshop\Core\Field('user');
            $oDb->execute("update oxuser set oxrights='user' where oxid=".$oDb->quote($this->getId()));
            $this->wwuserdeleted = false;
        }
        return $res;
    }
}