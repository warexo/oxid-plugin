<?php

namespace Warexo\Application\Model;

class BasketItem extends BasketItem_parent
{
    public function getArticle($blCheckProduct = false, $sProductId = null, $blDisableLazyLoading = false)
    {
        $oArticle = parent::getArticle($blCheckProduct,$sProductId,$blDisableLazyLoading);
        $params = $this->getPersParams();
        if (method_exists($oArticle, "setPersParam"))
            $oArticle->setPersParam($params);

        return $oArticle;
    }
}