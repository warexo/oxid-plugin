<?php

namespace Warexo\Application\Controller\Admin;

use OxidEsales\Eshop\Application\Controller\Admin\ArticleExtend;

class WarexoArticleExtend extends ArticleExtend
{
    public function save()
    {
        return parent::save();
    }

    public function render()
    {
        parent::render();
        return "@warexo/warexo_article_extend";
    }
}