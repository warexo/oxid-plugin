<?php

class agShopVersion
{
    public static function getInstance()
    {
        return oxNew(\OxidEsales\Eshop\Core\ShopVersion::class);
    }
}