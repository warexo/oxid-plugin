<?php

namespace Warexo\Application\Controller\Admin;

class UserList extends UserList_parent
{
    public function prepareWhereQuery($whereQuery, $fullQuery)
    {
        $whereQuery['oxuser.wwdeleted'] = 0;
        return parent::prepareWhereQuery($whereQuery, $fullQuery);
    }
}