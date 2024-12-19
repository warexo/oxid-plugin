<?php

class agArticleUtils
{
    public static function isPriceViewModeNetto($oArticle)
    {
        $blResult = (bool) agConfig::getInstance()->getConfigParam('blShowNetPrice');
        $oUser = $oArticle->getArticleUser();
        if ( $oUser ) {
            $blResult = $oUser->isPriceViewModeNetto();
        }

        return $blResult;
    }
    
    public static function getArticleFTPrice($oArticle)
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
        {
            $oPrice = $oArticle->getTPrice();
            if ($oPrice)
            {
                if (self::isPriceViewModeNetto($oArticle))
                    return agf::getLang()->formatCurrency( $oPrice->getNettoPrice() );
                else
                    return agf::getLang()->formatCurrency( $oPrice->getBruttoPrice() );
            }
        }
        else
            return $oArticle->getFPrice();
    }
    
    public static function getArticleFUnitPrice($oArticle)
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
        {
            if ( $oPrice = $oArticle->getUnitPrice() ) {
                if (self::isPriceViewModeNetto($oArticle))
                    return agf::getLang()->formatCurrency( $oPrice->getNettoPrice() );
                else
                    return agf::getLang()->formatCurrency( $oPrice->getBruttoPrice() );
            }
        }
        else if (method_exists($oArticle, "getFUnitPrice"))
            return $oArticle->getFUnitPrice();
        else if (method_exists($oArticle, "getPricePerUnit"))
            return $oArticle->getPricePerUnit();
    }
    
    public static function getArticleFPrice($oArticle)
    {
        if (agConfig::isVersionOrHigher('4.9.0'))
        {
            $oPrice = $oArticle->getPrice();
            if ($oPrice)
            {
                if (self::isPriceViewModeNetto($oArticle))
                    return agf::getLang()->formatCurrency( $oPrice->getNettoPrice() );
                else
                    return agf::getLang()->formatCurrency( $oPrice->getBruttoPrice() );
            }
        }
        else
            return $oArticle->getFPrice();
    }
    
    public static function saveTags($oArticle, $sTags)
    {
        if (method_exists($oArticle, "saveTags"))
            return $oArticle->saveTags($sTags);
        else
        {
            $oArticleTagList = oxNew('oxarticletaglist');
            $oArticleTagList->loadInLang($oArticle->getLanguage(), $oArticle->getId());
            $oArticleTagList->set($sTags);
            $oArticleTagList->save();
        }
    }
}