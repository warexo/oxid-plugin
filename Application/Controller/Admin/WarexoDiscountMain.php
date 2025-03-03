<?php

namespace Warexo\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\DiscountMain;

class WarexoDiscountMain extends DiscountMain
{
    public function save()
    {
        return parent::save();
    }

    public function render()
    {
        parent::render();
        return "warexo_discount_main";
    }
}