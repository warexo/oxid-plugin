<?php

namespace Warexo\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\CategoryMain;

class WarexoCategoryMain extends CategoryMain
{
    public function render()
    {
        parent::render();
        return "@warexo/warexo_category_main";
    }
}