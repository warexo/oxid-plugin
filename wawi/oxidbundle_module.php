<?php

class OxidBundleModule
{
    public function get_admin_widget_url()
    {
        $oConf = agConfig::getInstance();
        $credentialsUrl = $this->getUserCredentialsUrl();
        $url = $oConf->getCurrentShopUrl() . "admin/index.php?cl=adminwidget" . $credentialsUrl;
        return $url;
    }

    public function get_admin_page_widget_url($page)
    {
        $oConf = agConfig::getInstance();
        $credentialsUrl = $this->getUserCredentialsUrl();
        $url = $oConf->getCurrentShopUrl() . "admin/index.php?cl=adminwidget:" . $page . $credentialsUrl;
        return $url;
    }

    public function get_product_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oArticle = oxNew("oxarticle");
            $oArticle->load($oxid);

            $all_product_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_product_widgets = null;
                require_once $incFile;
                if (is_array($extra_product_widgets))
                    $all_product_widgets = array_merge($all_product_widgets, $extra_product_widgets);
            }
            foreach ($all_product_widgets as $product_widget) {
                if ($product_widget["article"] == $article) {
                    $widget = $this->createWidget($product_widget, $oxid);
                    $widget["shopurl"] = $oArticle->getLink();
                    if (!$oArticle->oxarticles__oxactive->value)
                        $widget["shopurl"] .= "&wrxpreviewtoken=" . $this->getTemporaryPreviewToken();
                    $widgets[] = $widget;
                }
            }
        }
        return $widgets;
    }

    public function get_discount_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oDiscount = oxNew("oxdiscount");
            $oDiscount->load($oxid);

            $all_discount_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_discount_widgets = null;
                require_once $incFile;
                if (is_array($extra_discount_widgets))
                    $all_discount_widgets = array_merge($all_discount_widgets, $extra_discount_widgets);
            }
            foreach ($all_discount_widgets as $discount_widget) {
                if ($discount_widget["article"] == $article)
                    $widgets[] = $this->createWidget($discount_widget, $oxid);
            }
        }
        return $widgets;
    }

    public function get_customer_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oUser = oxNew("oxuser");
            $oUser->load($oxid);

            $all_customer_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_customer_widgets = null;
                require_once $incFile;
                if (is_array($extra_customer_widgets))
                    $all_customer_widgets = array_merge($all_customer_widgets, $extra_customer_widgets);
            }
            foreach ($all_customer_widgets as $customer_widget) {
                if ($customer_widget["article"] == $article)
                    $widgets[] = $this->createWidget($customer_widget, $oxid);
            }
        }
        return $widgets;
    }

    public function get_productoption_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxselectlist where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxselectlist where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oSelectList = oxNew("oxselectlist");
            $oSelectList->load($oxid);

            $all_productoption_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_productoption_widgets = null;
                require_once $incFile;
                if (is_array($extra_productoption_widgets))
                    $all_productoption_widgets = array_merge($all_productoption_widgets, $extra_productoption_widgets);
            }
            foreach ($all_productoption_widgets as $productoption_widget) {
                if ($productoption_widget["article"] == $article)
                    $widgets[] = $this->createWidget($productoption_widget, $oxid);
            }
        }
        return $widgets;
    }

    public function get_order_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxorder where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxorder where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oOrder = oxNew("oxorder");
            $oOrder->load($oxid);

            $all_order_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_order_widgets = null;
                require_once $incFile;
                if (is_array($extra_order_widgets))
                    $all_order_widgets = array_merge($all_order_widgets, $extra_order_widgets);
            }
            foreach ($all_order_widgets as $order_widget) {
                if ($order_widget["article"] == $article) {
                    if ($order_widget['condition']) {
                        $res = false;
                        eval('$res = ' . $order_widget['condition'] . ';');
                        if (!$res)
                            continue;
                    }
                    $widgets[] = $this->createWidget($order_widget, $oxid);
                }
            }
        }
        return $widgets;
    }

    public function get_category_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oCategory = oxNew("oxcategory");
            $oCategory->load($oxid);

            $all_category_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_category_widgets = null;
                require_once $incFile;
                if (is_array($extra_category_widgets))
                    $all_category_widgets = array_merge($all_category_widgets, $extra_category_widgets);
            }
            foreach ($all_category_widgets as $category_widget) {
                if ($category_widget["article"] == $article) {
                    $widget = $this->createWidget($category_widget, $oxid);
                    $widget["shopurl"] = $oCategory->getLink();
                    $widgets[] = $widget;
                }
            }
        }
        return $widgets;
    }

    public function get_manufacturer_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxmanufacturers where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxmanufacturers where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oManufacturer = oxNew("oxmanufacturer");
            $oManufacturer->load($oxid);

            $all_manufacturer_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_manufacturer_widgets = null;
                require_once $incFile;
                if (is_array($extra_manufacturer_widgets))
                    $all_manufacturer_widgets = array_merge($all_manufacturer_widgets, $extra_manufacturer_widgets);
            }
            foreach ($all_manufacturer_widgets as $manuf_widget) {
                if ($manuf_widget["article"] == $article) {
                    $widget = $this->createWidget($manuf_widget, $oxid);
                    $widget["shopurl"] = $oManufacturer->getLink();
                    $widgets[] = $widget;
                }
            }
        }
        return $widgets;
    }

    public function get_attribute_widgets($wawiId, $oxidId, $article = false)
    {
        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $oxid = $oDb->getOne("select oxid from oxattribute where oxid=" . $oDb->quote($oxidId));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from oxattribute where wwforeignid=" . $oDb->quote($wawiId));
        $widgets = array();
        if ($oxid) {
            $oAttribute = oxNew("oxattribute");
            $oAttribute->load($oxid);

            $all_attribute_widgets = array();
            //global $extra_product_widgets;
            foreach (glob(getShopBasePath() . "wawi/*_widgets.inc.php") as $incFile) {
                $extra_attribute_widgets = null;
                require_once $incFile;
                if (is_array($extra_attribute_widgets))
                    $all_attribute_widgets = array_merge($all_attribute_widgets, $extra_attribute_widgets);
            }
            foreach ($all_attribute_widgets as $attr_widget) {
                if ($attr_widget["article"] == $article) {
                    $widget = $this->createWidget($attr_widget, $oxid);
                    $widgets[] = $widget;
                }
            }
        }
        return $widgets;
    }

    protected function createWidget($entity_widget, $oxid)
    {
        $oConf = agConfig::getInstance();
        $credentialsUrl = $this->getUserCredentialsUrl();
        $url = $oConf->getCurrentShopUrl() . "admin/index.php?cl=adminwidget:" . $entity_widget["controller"] . "&oxid=" . $oxid . $credentialsUrl;
        if (is_array($entity_widget["fields"]) && count($entity_widget["fields"]) > 0)
            $url .= "&admfields=" . base64_encode(implode(",", $entity_widget["fields"]));
        $widget = array(
            "title" => $entity_widget["title"],
            "url" => $url,
            "id" => $entity_widget["id"],
            "height" => $entity_widget["height"],
            "fields" => $entity_widget["fields"]);
        return $widget;
    }

    protected function getUserCredentialsUrl()
    {
        $user = $_REQUEST['user'];
        $pass = $_REQUEST['pass'];
        $oUser = oxNew("oxuser");
        $oUser->login($user, $pass);
        return "&user=" . $user . "&pass=" . md5($oUser->oxuser__oxpassword->value) . "&wawitoken=" . $this->getTemporaryWAWIToken();
    }

    protected function getTemporaryPreviewToken()
    {
        $oDb = oxDb::getDb();
        $wawitoken = md5(uniqid() . rand());
        $oDb->execute("insert into wawitokens (id, expired) values (" . $oDb->quote($wawitoken) . ",date_add(now(), interval 30 minute))");
        $oDb->execute("delete from wawitokens where expired < now()");
        return $wawitoken;
    }

    protected function getTemporaryWAWIToken()
    {
        $oDb = oxDb::getDb();
        $wawitoken = md5(uniqid() . rand());
        $oDb->execute("insert into wawitokens (id, expired) values (" . $oDb->quote($wawitoken) . ",date_add(now(), interval 3 minute))");
        $oDb->execute("delete from wawitokens where expired < now()");
        return $wawitoken;
        /*$wawitoken = $oDb->getOne("select id from wawitokens where used=0 limit 0,1");
        if (!$wawitoken)
            $this->generatetokens();
        $wawitoken = $oDb->getOne("select id from wawitokens where used=0 limit 0,1");
        if ($wawitoken)
            $oDb->execute("update wawitokens set expired=date_add(now(), interval 3 minute) where id=".$oDb->quote($wawitoken));
        return $wawitoken;*/
    }

    protected function generatetokens()
    {
        $oDb = oxDb::getDb();
        $oDb->execute("update wawitokens set id=MD5(UUID()), expired='0000-00-00 00:00:00',used=0 where used=1 or date_add(expired, interval 3 minute) < now()");
        if ($oDb->getOne("select count(id) from wawitokens where used=0 and expired='0000-00-00 00:00:00'") == 0) {
            $i = 0;
            while ($i < 1000) {
                $oDb->execute("insert into wawitokens (id) values (" . $oDb->quote(md5(uniqid() . rand())) . ")");
                $i++;
            }
        }
    }

    public function onAfterAddProductOption($data, $oSelectList)
    {

        //Parse icons and descriptions and store them
        $aValData = array();
        $aValIcons = array();

        $optionvalues = $data->optionvalues;
        usort($optionvalues, "cmp_by_sort");

        foreach ($optionvalues as $optionvalue) {
            $aValData[] = $optionvalue->description;
            $aValIcons[] = $optionvalue->icon;
        }

        $oSelectList->oxselectlist__wwvaldata = new oxField(implode('|', $aValData));
        $oSelectList->oxselectlist__wwvalicons = new oxField(implode('|', $aValIcons));
        $oSelectList->save();

        file_put_contents('log-test.txt', print_r($oSelectList, true));

    }
}

$oxidModule = new OxidBundleModule();
ModuleManager::getInstance()->registerModule($oxidModule);
ModuleManager::getInstance()->addEventListener(WAWIConnectorEvents::AFTER_ADD_PRODUCT_OPTION, $oxidModule, 'onAfterAddProductOption');