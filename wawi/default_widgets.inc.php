<?php

$extra_product_widgets = array(
    array("title" => "Crossselling (Oxid)", "id" => "crossselling", "controller" => "article_crossselling", "height" => "70px"),
    array("title" => "SEO (Oxid)", "id"=>"seo", "controller" => "article_seo", "height" => "370px"),
    array("title" => "Erweitert (Oxid)", "id"=>"oxidextend", "controller" => "wawi_article_extend", "height" => "510px")
);

$extra_category_widgets = array(
    array("title" => "SEO (Oxid)", "id" => "seo", "controller" => "category_seo", "height" => "370px"),
    array("title" => "Erweitert (Oxid)", "id"=>"oxidextend", "controller" => "wawi_category_main", "height" => "220px"),
    array("title" => "Sortierung (Oxid)", "id"=>"oxidorder", "controller" => "category_order", "height" => "60px")
    /*array("title" => "Oxid Extra", "id" => "oxidextra", "controller" => "category_main", "height" => "190px", 
            "fields"=>array(
                "editval[oxcategories__oxdefsort]",
                "editval[oxcategories__oxpricefrom]",
                "editval[oxcategories__oxskipdiscounts]",
                "editval[oxcategories__oxtemplate]",
                "save")),*/
);

$extra_manufacturer_widgets = array(
    array("title" => "SEO (Oxid)", "id" => "seo", "controller" => "manufacturer_seo", "height" => "370px"),
    /*array("title" => "Oxid Extra", "id" => "oxidextra", "controller" => "category_main", "height" => "190px", 
            "fields"=>array(
                "editval[oxcategories__oxdefsort]",
                "editval[oxcategories__oxpricefrom]",
                "editval[oxcategories__oxskipdiscounts]",
                "editval[oxcategories__oxtemplate]",
                "save")),*/
);

$extra_customer_widgets = array(
    array("title" => "Erweitert (Oxid)", "id"=>"oxidextend", "controller" => "wawi_user_extend", "height" => "160px")
);

$extra_discount_widgets = array(
    array("title" => "Erweitert (Oxid)", "id"=>"oxidextend", "controller" => "wawi_discount_main", "height" => "160px")
);

$extra_attribute_widgets = array(
    array("title" => "Kategorien (Oxid)", "id"=>"oxidcategories", "controller" => "wawi_attribute_category", "height" => "160px")
);