<?php

namespace Warexo\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\UtilsView;

class ShopControl extends ShopControl_parent
{
    protected $_aDisabledFunctions = array(
        "article_main" => array("save","addtocategory","copyarticle"),
        "article_extend" => array("save","deletemedia","updatemedia"),
        "article_pictures" => array("save","deletepicture"),
        "article_files" => array("upload","deletefile"),
        "article_stock" => array("save","addprice","updateprices","deleteprice"),
        "article_variant" => array("savevariant","savevariants","changename","deletevariant","addsel"),
        "category_main" => array("save","deletepicture"),
        "category_text" => array("save"),
        "attribute_main" => array("save","saveinnlang"),
        "selectlist_main" => array("save","saveinnlang","delfields","addfield","changefield"),
        "manufacturer_main" => array("save","saveinnlang"),
        "order_overview" => array("sendorder","createpdf","changefolder"),
        "order_main" => array("save","sendorder","resetorder"),
        "order_address" => array("save"),
        "order_article" => array("addthisarticle","deletethisarticle","storno","updateorder"),
        "order_list" => array("storno","deleteentry"),
        "voucherserie_main" => array("save"),
        "voucherserie_generate" => array("start"),
        "usergroup_main" => array("save"),

        "OxidEsales\Eshop\Application\Controller\Admin\ArticleMain" => array("save","addtocategory","copyarticle"),
        "OxidEsales\Eshop\Application\Controller\Admin\ArticleExtend" => array("save","deletemedia","updatemedia"),
        "OxidEsales\Eshop\Application\Controller\Admin\ArticlePictures" => array("save","deletepicture"),
        "OxidEsales\Eshop\Application\Controller\Admin\ArticleFiles" => array("upload","deletefile"),
        "OxidEsales\Eshop\Application\Controller\Admin\ArticleStock" => array("save","addprice","updateprices","deleteprice"),
        "OxidEsales\Eshop\Application\Controller\Admin\ArticleVariant" => array("savevariant","savevariants","changename","deletevariant","addsel"),
        "OxidEsales\Eshop\Application\Controller\Admin\CategoryMain" => array("save","deletepicture"),
        "OxidEsales\Eshop\Application\Controller\Admin\CategoryText" => array("save"),
        "OxidEsales\Eshop\Application\Controller\Admin\AttributeMain" => array("save","saveinnlang"),
        "OxidEsales\Eshop\Application\Controller\Admin\SelectListMain" => array("save","saveinnlang","delfields","addfield","changefield"),
        "OxidEsales\Eshop\Application\Controller\Admin\ManufacturerMain" => array("save","saveinnlang"),
        "OxidEsales\Eshop\Application\Controller\Admin\OrderOverview" => array("sendorder","createpdf","changefolder"),
        "OxidEsales\Eshop\Application\Controller\Admin\OrderMain" => array("save","sendorder","resetorder"),
        "OxidEsales\Eshop\Application\Controller\Admin\OrderAddress" => array("save"),
        "OxidEsales\Eshop\Application\Controller\Admin\OrderArticle" => array("addthisarticle","deletethisarticle","storno","updateorder"),
        "OxidEsales\Eshop\Application\Controller\Admin\OrderList" => array("storno","deleteentry"),
        "OxidEsales\Eshop\Application\Controller\Admin\VoucherserieMain" => array("save"),
        "OxidEsales\Eshop\Application\Controller\Admin\VoucherserieGenerate" => array("start"),
        "OxidEsales\Eshop\Application\Controller\Admin\UserGroupMain" => array("save"),
    );

    protected $_blAdminWidget = false;
    protected $_aAdminFields = null;

    public function aggrowawi_autoload($sClass){

        include getShopBasePath() . '/modules/warexo/metadata.php';

        if(isset($aModule['files']) && isset($aModule['files'][$sClass])){
            include( getShopBasePath() . 'modules/' . $aModule['files'][$sClass]);
        }
    }

    protected function render($oViewObject)
    {
        $sOutput = parent::render($oViewObject);
        if ($this->_blAdminWidget)
        {
            $oConf = Registry::getConfig();
            $sSearch = "</body>";
            $css = '<link rel="stylesheet" href="'.$oConf->getSslShopUrl()."modules/warexo/out/src/wawi.css?5".'" />';
            $oSmarty = Registry::get(UtilsView::class)->getSmarty();
            if ($this->_aAdminFields)
            {
                $oSmarty->assign('admfields', $this->_aAdminFields);
                $oSmarty->assign('sadmfields',  base64_encode(implode(",",$this->_aAdminFields)));
            }
            if (strpos(Registry::get(Request::class)->getRequestParameter("cl", "agcms_list")) === FALSE)
                $script = $oSmarty->fetch( getShopBasePath().'modules/warexo/out/src/extracode.tpl', md5(uniqid()) );
            $sReplace = $css.$script."</body>";
            $sOutput = ltrim($sOutput);
            if ( ($pos = stripos( $sOutput, $sSearch )) !== false) {
                $sOutput = substr_replace($sOutput, $sReplace, $pos, strlen($sSearch));
            }
        }
        if (!$this->getUser() && @$_COOKIE['wrxextranet'])
        {
            $oConf = Registry::getConfig();
            $aggrowawiurl = $oConf->getShopConfVar('extraneturl', null, 'module:aggrowawi');
            $sSearch = "</body>";
            $sOutput = ltrim($sOutput);
            if (($pos = stripos( $sOutput, $sSearch )) !== false)
            {
                $iframe = '<iframe src="'.$aggrowawiurl.'/extranet/account/logout" style="width:1px;height: 1px; visibility: hidden">';
                $sReplace = $iframe."</body>";
                $sOutput = substr_replace($sOutput, $sReplace, $pos, strlen($sSearch));
                unset($_COOKIE["wrxextranet"]);
                setcookie("wrxextranet", "", time()-3600, '/');
            }
        }
        return $sOutput;
    }

    protected function setAgentCookies()
    {
        $oConf = Registry::getConfig();
        if ($oConf->getShopConfVar("wawiagentparameters"))
        {
            $agentParameters = $oConf->getShopConfVar("wawiagentparameters");
            $all = "";
            foreach ($_GET as $key=>$val)
            {
                foreach ($agentParameters as $param)
                {
                    if (strpos($key, $param) !== FALSE)
                    {
                        if ($all)
                            $all .= "&";
                        $all .= $key.'='.$val;
                    }
                }
            }
            if ($all)
                setcookie('wwagentparameter', $all, 60*60*24*14+time(), '/');
        }

    }

    protected function process( $sClass, $sFunction, $aParams = null, $aViewsChain = null )
    {
        spl_autoload_register(array($this,'aggrowawi_autoload'));
        $oConf = Registry::getConfig();
        $this->setAgentCookies();
        return parent::process($sClass, $sFunction, $aParams, $aViewsChain);
    }

    protected function initializeViewObject($sClass, $sFunction, $aParams = null, $aViewsChain = null)
    {
        if ($this->isAdmin() && Registry::getSession()->getVariable("auth2"))
            Registry::getSession()->setVariable("auth", Registry::getSession()->getVariable("auth2"));
        if ($this->isAdmin() && strpos($sClass,"adminwidget") === 0)
        {
            $arr = explode(":",$sClass);
            if (count($arr) > 1)
                $controller = $arr[1];
            else
                $controller = "admin_start";
            $this->setAdminMode(true);
            if (count($arr) > 1)
                $this->_blAdminWidget = true;
            if (count(Registry::get(UtilsServer::class)->getOxCookie() ) == 0)
            {
                Registry::get(UtilsServer::class)->setOxCookie("aggrowawi", "1");
                $_COOKIE["aggrowawi"] = "1";
            }
            if (!Registry::getSession()->getVariable( "auth") && Registry::getSession()->getVariable("auth2"))
                Registry::getSession()->setVariable("auth", Registry::getSession()->getVariable("auth2"));
            $wawitoken = Registry::get(Request::class)->getRequestParameter("wawitoken");
            $oDb = DatabaseProvider::getDb();
            if (!Registry::getSession()->getVariable("auth"))
            {
                if (!$wawitoken || !$oDb->getOne("select id from wawitokens where id=".$oDb->quote($wawitoken)))
                {
                    echo "Invalid credentials. Unknown token";
                    exit();
                }
                if ($oDb->getOne("select used from wawitokens where id=".$oDb->quote($wawitoken)." and expired < now()"))
                {
                    echo "Invalid credentials. Token is expired";
                    exit();
                }
                $user = Registry::get(Request::class)->getRequestParameter("user");
                $password = Registry::get(Request::class)->getRequestParameter("pass");
                $userid = $oDb->getOne("select oxid from oxuser where oxusername=".$oDb->quote($user));
                if ($userid)
                {
                    $oUser = oxNew("oxuser");
                    $oUser->load($userid);
                    if (md5($oUser->oxuser__oxpassword->value) == $password && $oUser->oxuser__oxrights->value == "malladmin")
                    {
                        Registry::getSession()->setVariable("auth", $userid);
                        Registry::getSession()->setVariable("auth2", $userid);
                        //$oDb->execute("delete from wawitokens where id=".$oDb->quote($wawitoken));
                    }
                    else
                    {
                        echo "Invalid credentials. Invalid password";
                        exit();
                    }
                }
                else
                {
                    echo "Invalid credentials. Invalid user";
                    exit();
                }
            }
            else if ($wawitoken)
            {
                //$oDb->execute("delete from wawitokens where id=".$oDb->quote($wawitoken));
            }

            $stoken = Registry::getSession()->getSessionChallengeToken();
            $_GET['stoken'] = $stoken;
            $_POST['stoken'] = $stoken;
            $myConfig = $this->getConfig();
            if (Registry::get(Request::class)->getRequestParameter("admfields"))
                $this->_aAdminFields = explode(",", base64_decode(Registry::get(Request::class)->getRequestParameter("admfields")));
            // creating current view object
            if (method_exists($this, "getControllerClass") && strpos($controller, "\\") === FALSE)
            {
                $oViewObject = oxNew($this->getControllerClass($controller));
            }
            else
                $oViewObject = oxNew($controller);

            // store this call
            $oViewObject->setClassName($controller);
            $oViewObject->setFncName($sFunction);
            if (method_exists($oViewObject, "setViewParameters"))
                $oViewObject->setViewParameters( $aParams );
            $myConfig->setActiveView( $oViewObject );

            // init class
            $oViewObject->init();
            return $oViewObject;
        }
        $oConf = $this->getConfig();

        if (!$this->isAdmin())
            return parent::initializeViewObject($sClass, $sFunction, $aParams, $aViewsChain);
        $confDisabledFunctions = $oConf->getConfigParam('aWAWIDisabledFunctions');
        if (is_array($confDisabledFunctions))
        {
            $funcs = $confDisabledFunctions[$sClass];
        }
        else
            $funcs = $this->_aDisabledFunctions[$sClass];

        if ($funcs && in_array(strtolower($sFunction), $funcs) && Registry::getSession()->getVariable("auth"))
        {
            //agUtilsView::getInstance()->addErrorToDisplay( "Bitte verwenden Sie WAWI" );
            echo '<div style="font-family: Arial; font-size: 20px; padding: 20px; color: red; font-weight: bold;">Änderungen wurden nicht gespeichert! Bitte nutzen Sie hierfür ausschließlich Warexo.</div>';
            die();

        }
        return parent::initializeViewObject($sClass, $sFunction, $aParams, $aViewsChain);
    }

    protected function runOnce()
    {
        $myConfig = Registry::getConfig();
        if (!$myConfig->getConfigParam('wawiNotUseSavedOfferBaskets') && !$this->isAdmin())
        {
            $oUser = $this->getUser();
            if ($oUser->oxuser__wwupdatebasket->value)
            {
                $oBasket = Registry::getSession()->getBasket();
                $oBasket->load();
                $oBasket->calculateBasket(true);
                Registry::getSession()->setVariable("basket", serialize($oBasket));
                $oUser->oxuser__wwupdatebasket = new oxField(0);
                $oUser->save();
                Registry::getSession()->freeze();

            }
        }
        parent::runOnce();
    }
}