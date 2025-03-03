<?php

namespace Warexo\Application\Controller\Admin;

class UserList extends UserList_parent
{
    public function prepareWhereQuery($whereQuery, $fullQuery)
    {
        $aWhere['oxuser.wwdeleted'] = 0;
        return parent::prepareWhereQuery($aWhere, $sQueryFull);
    }
}