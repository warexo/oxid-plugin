<?php

namespace Warexo\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Request;

class WAWITokenHelper
{
    public function getUser()
    {
        $oUser = oxNew('oxuser');
        if ($oUser->loadActiveUser()) {
            return $oUser;
        }
    }

    public function generateToken()
    {
        $oDb = DatabaseProvider::getDb();
        $wawitoken = md5(uniqid().rand());
        $oUser = $this->getUser();
        if (!$oUser)
            return null;
        $id = $oDb->getOne("select id from wawitokens where userid=".$oDb->quote($oUser->oxuser__oxid->value)." and expired > now()");
        if ($id)
        {
            $oDb->execute("update wawitokens set expired=date_add(now(), interval 20 minute) where id=".$oDb->quote($id));
            $wawitoken = $id;
        }
        else
            $oDb->execute("insert into wawitokens (id, userid, expired) values (".$oDb->quote($wawitoken).",".$oDb->quote($oUser->oxuser__oxid->value).",date_add(now(), interval 20 minute))");
        $oDb->execute("delete from wawitokens where expired < now()");
        return $wawitoken.":".$oUser->oxuser__oxid->value.":".$oUser->oxuser__wwforeignid->value;
    }
}