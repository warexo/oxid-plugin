<?php

namespace Warexo\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\AttributeCategory;

class WarexoAttributeCategory extends AttributeCategory
{
    public function render()
    {
        parent::render();
        return "warexo_attribute_category";
    }
}