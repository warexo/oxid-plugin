<?php

namespace Warexo\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\UserExtend;

class WarexoUserExtend extends UserExtend
{
    public function save()
    {
        return parent::save();
    }

    public function render()
    {
        parent::render();
        return "@warexo/warexo_user_extend";
    }
}