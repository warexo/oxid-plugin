<?php

$sMetadataVersion = '2.1';

$aModule = array(
    'id'           => 'warexo',
    'title'        => 'Warexo Extension',
    'description'  => 'Warexo Extension',
    'thumbnail'    => '',
    'version'      => '2.0',
    'author'       => 'Aggrosoft',
    'extend'      => array(
        \OxidEsales\Eshop\Application\Model\Order::class => \Warexo\Application\Model\Order::class,
        \OxidEsales\Eshop\Application\Model\User::class => \Warexo\Application\Model\User::class,
        \OxidEsales\Eshop\Core\ShopControl::class => \Warexo\Core\ShopControl::class,
        \OxidEsales\Eshop\Application\Model\Basket::class => \Warexo\Application\Model\Basket::class,
        \OxidEsales\Eshop\Application\Model\Voucher::class => \Warexo\Application\Model\Voucher::class,
        \OxidEsales\Eshop\Core\Language::class => \Warexo\Core\Language::class,
        \OxidEsales\Eshop\Core\UtilsView::class => \Warexo\Core\UtilsView::class,
        \OxidEsales\Eshop\Application\Model\Article::class => \Warexo\Application\Model\Article::class,
        \OxidEsales\Eshop\Application\Model\Article::class => \Warexo\Application\Model\Category::class,
        \OxidEsales\Eshop\Application\Model\SelectList::class => \Warexo\Application\Model\SelectList::class,
        \OxidEsales\Eshop\Application\Model\ArticleList::class => \Warexo\Application\Model\ArticleList::class,
        \OxidEsales\Eshop\Application\Controller\ThankYouController::class => \Warexo\Application\Controller\ThankYouController::class,
        \OxidEsales\Eshop\Application\Model\AmountPriceList::class => \Warexo\Application\Model\AmountPriceList::class,
        \OxidEsales\Eshop\Core\ViewConfig::class => \Warexo\Core\ViewConfig::class,
        \OxidEsales\Eshop\Application\Model\UserList::class => \Warexo\Application\Model\UserList::class,
        \OxidEsales\Eshop\Application\Controller\Admin\UserList::class => \Warexo\Application\Controller\Admin\UserList::class,
        \OxidEsales\Eshop\Application\Model\BasketItem::class => \Warexo\Application\Model\BasketItem::class,
        \OxidEsales\Eshop\Application\Model\Discount::class => \Warexo\Application\Model\Discount::class,
        \OxidEsales\Eshop\Core\WidgetControl::class => \Warexo\Core\WidgetControl::class,
        \OxidEsales\Eshop\Application\Model\VatSelector::class => \Warexo\Application\Model\VatSelector::class,
        \OxidEsales\Eshop\Core\Config::class => \Warexo\Core\Config::class
    ),
    'events'       => array(
        'onActivate'   => 'WarexoInstaller::onActivate',
    ),
    'controllers' => array(
        //installer
       /* 'wawi_article_extend' => 'aggrowawi/controllers/admin/wawi_article_extend.php',
        'wawi_category_main' => 'aggrowawi/controllers/admin/wawi_category_main.php',
        'wawi_user_extend' => 'aggrowawi/controllers/admin/wawi_user_extend.php',
        'wawi_discount_main' => 'aggrowawi/controllers/admin/wawi_discount_main.php',
        'wawi_attribute_category' => 'aggrowawi/controllers/admin/wawi_attribute_category.php',
        'wawi_agcms_product_editor' => 'aggrowawi/controllers/admin/wawi_agcms_product_editor.php',
        'account_tickets' => 'aggrowawi/controllers/account_tickets.php',
        'account_ticket' => 'aggrowawi/controllers/account_ticket.php',
        'account_customgrid' => 'aggrowawi/controllers/account_customgrid.php',
        'account_licenses' => 'aggrowawi/controllers/account_licenses.php',
        'account_subscription_contracts' => 'aggrowawi/controllers/account_subscription_contracts.php',
        'account_subscription_contract' => 'aggrowawi/controllers/account_subscription_contract.php'*/
    ),
    'templates' => array(
        /*'agextranetpage.tpl' => 'aggrowawi/views/flow/tpl/agextranetpage.tpl',
        'account_tickets.tpl' => 'aggrowawi/views/flow/tpl/account_tickets.tpl',
        'account_ticket.tpl' => 'aggrowawi/views/flow/tpl/account_ticket.tpl',
        'account_licenses.tpl' => 'aggrowawi/views/flow/tpl/account_licenses.tpl',
        'account_customgrid.tpl' => 'aggrowawi/views/flow/tpl/account_customgrid.tpl',
        'account_subscription_contracts.tpl' => 'aggrowawi/views/flow/tpl/account_subscription_contracts.tpl',
        'account_subscription_contract.tpl' => 'aggrowawi/views/flow/tpl/account_subscription_contract.tpl',*/
    ),
    'settings' => array(
        array('group' => 'main', 'name' => 'extraneturl', 'type' => 'str'),
        array('group' => 'main', 'name' => 'extranetactive', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'extranetordersactive', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'extranetticketsactive', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'extranetlicensesactive', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'extranetdev', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiexportparentcategories', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'extranetsubscriptioncontractsactive', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiagentparameters', 'type' => 'arr', 'value' => ''),
        array('group' => 'main', 'name' => 'wawiexportaccessories', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiexportcrosssellings', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiusediscountaccessories', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiuseoss', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiusenettofoross', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawishownetpriceforgroups', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawienternetpriceforgroups', 'type' => 'bool', 'value' => 'false'),
    ),
    'blocks' => array(    
       /* array('template' => 'page/details/inc/productmain.tpl', 'block'=>'details_productmain_persparams', 'file'=>'details_productmain_persparams.tpl'),
        array('template' => 'page/checkout/thankyou.tpl', 'block'=>'checkout_thankyou_info', 'file'=>'checkout_thankyou_info.tpl'),
        array('template' => 'article_main.tpl', 'block'=>'admin_article_main_form','file'=>'article_main.tpl'),
        array('template' => 'include/category_main_form.tpl', 'block'=>'admin_category_main_form','file'=>'category_main.tpl'),
        array('template' => 'selectlist_main.tpl', 'block'=>'admin_selectlist_main_form','file'=>'selectlist_main.tpl'),
        array('template' => 'attribute_main.tpl', 'block'=>'admin_attribute_main_form','file'=>'attribute_main.tpl'),
        array('template' => 'manufacturer_main.tpl', 'block'=>'admin_manufacturer_main_form','file'=>'manufacturer_main.tpl'),
        array('template' => 'deliveryset_main.tpl', 'block'=>'admin_deliveryset_main_form','file'=>'deliveryset_main.tpl'),
        array('template' => 'page/account/order.tpl', 'block'=>'account_order_history','file'=>'account_order.tpl'),
        array('template' => 'page/account/inc/account_menu.tpl', 'block'=>'account_menu','file'=>'account_menu.tpl'),
        array('template' => 'page/account/dashboard.tpl', 'block'=>'account_dashboard_col1','file'=>'account_dashboard_col1.tpl'),
        array('template' => 'user_main.tpl', 'block'=>'admin_user_main_form','file'=>'user_main.tpl'),*/
    )
);