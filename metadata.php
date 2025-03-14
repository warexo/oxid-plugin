<?php

$sMetadataVersion = '2.1';

$aModule = array(
    'id'           => 'warexo',
    'title'        => 'Warexo Extension',
    'description'  => 'Warexo Extension',
    'thumbnail'    => '',
    'version'      => '1.0.14',
    'author'       => 'Aggrosoft',
    'extend'      => array(
        \OxidEsales\Eshop\Application\Model\Order::class => \Warexo\Application\Model\Order::class,
        \OxidEsales\Eshop\Application\Model\User::class => \Warexo\Application\Model\User::class,
        \OxidEsales\Eshop\Core\ShopControl::class => \Warexo\Core\ShopControl::class,
        \OxidEsales\Eshop\Application\Model\Basket::class => \Warexo\Application\Model\Basket::class,
        \OxidEsales\Eshop\Application\Model\Voucher::class => \Warexo\Application\Model\Voucher::class,
        \OxidEsales\Eshop\Core\UtilsView::class => \Warexo\Core\UtilsView::class,
        \OxidEsales\Eshop\Application\Model\Article::class => \Warexo\Application\Model\Article::class,
        \OxidEsales\Eshop\Application\Model\Category::class => \Warexo\Application\Model\Category::class,
        \OxidEsales\Eshop\Application\Model\SelectList::class => \Warexo\Application\Model\SelectList::class,
        \OxidEsales\Eshop\Application\Model\ArticleList::class => \Warexo\Application\Model\ArticleList::class,
        \OxidEsales\Eshop\Application\Controller\ThankYouController::class => \Warexo\Application\Controller\ThankYouController::class,
        \OxidEsales\Eshop\Application\Model\AmountPriceList::class => \Warexo\Application\Model\AmountPriceList::class,
        \OxidEsales\Eshop\Core\ViewConfig::class => \Warexo\Core\ViewConfig::class,
        \OxidEsales\Eshop\Application\Controller\Admin\UserList::class => \Warexo\Application\Controller\Admin\UserList::class,
        \OxidEsales\Eshop\Application\Model\BasketItem::class => \Warexo\Application\Model\BasketItem::class,
        \OxidEsales\Eshop\Application\Model\Discount::class => \Warexo\Application\Model\Discount::class,
        \OxidEsales\Eshop\Core\WidgetControl::class => \Warexo\Core\WidgetControl::class,
        \OxidEsales\Eshop\Application\Model\VatSelector::class => \Warexo\Application\Model\VatSelector::class,
        \OxidEsales\Eshop\Core\Config::class => \Warexo\Core\Config::class
    ),
    'events'       => array(
        'onActivate'   => '\Warexo\Core\WarexoConnectorInstaller::onActivate',
    ),
    'controllers' => array(
        //installer
        'wawi_article_extend' => \Warexo\Application\Controller\Admin\WarexoArticleExtend::class,
        'wawi_category_main' => \Warexo\Application\Controller\Admin\WarexoCategoryMain::class,
        'wawi_user_extend' => \Warexo\Application\Controller\Admin\WarexoUserExtend::class,
        'wawi_discount_main' => \Warexo\Application\Controller\Admin\WarexoDiscountMain::class,
        'wawi_attribute_category' => \Warexo\Application\Controller\Admin\WarexoAttributeCategory::class,
        'wawi_agcms_product_editor' => \Warexo\Application\Controller\Admin\WarexoAgcmsProductEditor::class,
        'account_tickets' => \Warexo\Application\Controller\Admin\AccountTickets::class,
        'account_ticket' => \Warexo\Application\Controller\Admin\AccountTicket::class,
        'account_customgrid' => \Warexo\Application\Controller\Admin\AccountCustomGrid::class,
        'account_licenses' => \Warexo\Application\Controller\Admin\AccountLicenses::class,
        'account_subscription_contracts' => \Warexo\Application\Controller\Admin\AccountSubscriptionContracts::class,
        'account_subscription_contract' => \Warexo\Application\Controller\Admin\AccountSubscriptionContract::class,
    ),
    'templates' => array(
        'agextranetpage.tpl' => 'warexo/views/tpl/agextranetpage.tpl',
        'account_tickets.tpl' => 'warexo/views/tpl/account_tickets.tpl',
        'account_ticket.tpl' => 'warexo/views/tpl/account_ticket.tpl',
        'account_licenses.tpl' => 'warexo/views/tpl/account_licenses.tpl',
        'account_customgrid.tpl' => 'warexo/views/tpl/account_customgrid.tpl',
        'account_subscription_contracts.tpl' => 'warexo/views/tpl/account_subscription_contracts.tpl',
        'account_subscription_contract.tpl' => 'warexo/views/tpl/account_subscription_contract.tpl'
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
        array('group' => 'main', 'name' => 'wawiagentparameters', 'type' => 'arr', 'value' => []),
        array('group' => 'main', 'name' => 'wawiexportaccessories', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiexportcrosssellings', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiusediscountaccessories', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiuseoss', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawiusenettofoross', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawishownetpriceforgroups', 'type' => 'bool', 'value' => 'false'),
        array('group' => 'main', 'name' => 'wawienternetpriceforgroups', 'type' => 'bool', 'value' => 'false'),
    ),
    'blocks' => array(    
        array('template' => 'page/details/inc/productmain.tpl', 'block'=>'details_productmain_persparams', 'file'=>'views/blocks/frontend/details_productmain_persparams.tpl'),
        array('template' => 'page/checkout/thankyou.tpl', 'block'=>'checkout_thankyou_info', 'file'=>'views/blocks/frontend/checkout_thankyou_info.tpl'),
        array('template' => 'page/account/order.tpl', 'block'=>'account_order_history','file'=>'views/blocks/frontend/account_order.tpl'),
        array('template' => 'page/account/inc/account_menu.tpl', 'block'=>'account_menu','file'=>'views/blocks/frontend/account_menu.tpl'),
        array('template' => 'page/account/dashboard.tpl', 'block'=>'account_dashboard_col1','file'=>'views/blocks/frontend/account_dashboard_col1.tpl')
    )
);