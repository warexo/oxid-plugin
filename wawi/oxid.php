<?php

//Bootstrap Oxid

if (file_exists(dirname(__FILE__) . "/../bootstrap.php"))
    require_once dirname(__FILE__) . "/../bootstrap.php";
else
    require_once dirname(__FILE__) . "/../bootstrapwawi.php";

require_once('iconnector.php');
/*
    if (!file_exists('OxidFieldsContainerAdditional.php'))
    {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode("wawi/OxidFieldsContainerAdditional.php missing. Copy this file from changed_full");
        exit();
    }
	*/
require_once('OxidFieldsContainer.php');
require_once('events.php');
include_once('modules.php');
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);

if (!defined("ADODB_FETCH_ASSOC"))
    define("ADODB_FETCH_ASSOC", 2);

class OxidConnector extends OxidFieldsContainer implements IConnector
{
    public $moduleManager;

    public function __construct()
    {
        $this->moduleManager = ModuleManager::getInstance();
        $this->moduleManager->setConnector($this);
        parent::__construct();
    }

    public function auth($user, $pass)
    {
        $oUser = oxNew("oxuser");
        return $oUser->login($user, $pass) && ($oUser->oxuser__oxrights->value == "malladmin" || $oUser->oxuser__oxrights->value === "1");
    }

    protected $_itemsPerPage = 10;

    public function ping()
    {
        return 'OK';
    }

    protected function getCount($sTable, $fromDate = null, $condition = null)
    {
        $oDb = oxDb::getDb();
        $sQ = "SELECT COUNT(*) FROM $sTable" . $condition;
        if ($fromDate) {
            $conf = agConfig::getInstance();
            $sVersion = agShopVersion::getInstance()->getVersion();
            if ($sTable == "oxuser" && version_compare($sVersion, '4.7.0') == -1)
                $sQ .= " where timestampdiff(second,'" . $fromDate . "',oxcreate) > 0 ";
            else
                $sQ .= " where timestampdiff(second,'" . $fromDate . "',oxtimestamp) > 0  and oxtimestamp <= NOW() ";
            if ($sTable == "oxorder") {
                if ($conf->getConfigParam('wawiExportOrdersOnlyFromDate')) {
                    $sQ .= " and oxorderdate >= " . $oDb->quote($conf->getConfigParam('wawiExportOrdersOnlyFromDate')) . " ";
                }
            }
        }
        if (!$fromDate && ($sTable == "oxorder" || $sTable == "oxuser" || $sTable == "oxobject2category" || $sTable == "oxvouchers" || $sTable == "oxobject2group")) {
            return array('count' => intval($oDb->getOne($sQ)), 'itemsPerPage' => 100);
        }
        return array('count' => intval($oDb->getOne($sQ)), 'itemsPerPage' => $this->_itemsPerPage);
    }


    protected function getLimit($sTable, $page, $fromDate = null, $user = false)
    {
        if ($fromDate) {
            $oDb = oxDb::getDb();
            $conf = agConfig::getInstance();
            $sVersion = agShopVersion::getInstance()->getVersion();
            $iVersion = intval(str_replace('.', '', $sVersion));
            if ($user && version_compare($sVersion, '4.7.0') == -1)
                $sQ .= " where timestampdiff(second,'" . $fromDate . "',oxcreate) > 0 ";
            else
                $sQ .= " where timestampdiff(second,'$fromDate',oxtimestamp) > 0 and oxtimestamp <= NOW() ";
            if ($sTable == "oxorder") {
                if ($conf->getConfigParam('wawiExportOrdersOnlyFromDate')) {
                    $sQ .= " and oxorderdate >= " . $oDb->quote($conf->getConfigParam('wawiExportOrdersOnlyFromDate')) . " ";
                }
                if ($conf->getConfigParam('wawiExportOrdersSqlCondition')) {
                    $sQ .= " and " . $conf->getConfigParam('wawiExportOrdersSqlCondition') . " ";
                }
                $sQ .= " and wwpending = 0 ";
                $sQ .= " order by oxorderdate desc, oxid asc ";
            }
        } else if ($sTable == "oxorder") {
            $sQ .= " order by oxorderdate asc ";
        }
        if (!$fromDate && ($sTable == "oxorder" || $sTable == "oxuser" || $sTable == "oxobject2category" || $sTable == "oxvouchers" || $sTable == "oxobject2group")) {
            $sQ .= ' LIMIT ' . ($page * 100) . ', ' . 100;
        } else
            $sQ .= ' LIMIT ' . ($page * $this->_itemsPerPage) . ', ' . $this->_itemsPerPage;

        return $sQ;
    }

    public function set_shemes($data)
    {
        $oDb = oxDb::getDb();
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_sheme($value);
        }
        $oDb->updateViews();
        $this->cleartmp();
    }

    public function add_sheme($data)
    {
        $oDb = oxDb::getDb();
        $table_map = array(
            "product" => "oxarticles",
            "attribute" => "oxattribute",
            "category" => "oxcategories",
            "customer" => "oxuser",
            "productoption" => "oxselectlist",
        );
        $table = $table_map[$data->table];
        $field = $data->field;
        if ($table && strpos($field, ";") === FALSE && strpos($field, "%") === FALSE && strpos($field, " ") === FALSE)//prevent sql injection
        {
            $oDb->execute("alter table $table add $field varchar(255) not null");
        }
    }

    protected function resetLanguage($oEntity)
    {
        foreach ($this->_getLanguageArray() as $lang) {
            if ($lang->selected) {
                $oEntity->setLanguage($lang->id);
            }
        }
    }

    protected function getLanguageLocale($id)
    {
        foreach ($this->_getLanguageArray() as $lang) {
            if ($lang->id == $id)
                return $lang->abbr;
        }
        return null;
    }

    protected function get_i18n($oEntity, $multilangfields, $table)
    {
        $oStammEntity = $oEntity;
        $oxid = $oEntity->getId();
        $i18n = array();
        foreach ($this->_getLanguageArray() as $lang) {
            if (!$lang->selected) {
                $oEntity = oxNew($oEntity->getClassName());
                $oEntity->loadInLang($lang->id, $oxid);

                foreach ($this->{$multilangfields} as $oxid_field => $wawi_field) {
                    if (strpos($oxid_field, ",") !== FALSE) {
                        $field_arr = explode(",", $oxid_field);
                        $fval = "";
                        for ($i = count($field_arr) - 1; $i >= 0; $i--) {
                            $field = $table . "__" . $field_arr[$i];
                            $fval = $oEntity->{$field}->value ? $oEntity->{$field}->value : $oStammEntity->{$field}->value;
                            if ($fval)
                                break;
                        }
                        $i18n[$lang->abbr][$wawi_field] = $fval;
                    } else {
                        $field = $table . "__" . $oxid_field;
                        $i18n[$lang->abbr][$wawi_field] = $oEntity->{$field}->rawValue ? $oEntity->{$field}->rawValue : $oStammEntity->{$field}->rawValue;
                        if ($field == "oxarticles__oxlongdesc" && method_exists($oEntity, "getLongDescription")) {
                            $oDesc = $oEntity->getLongDescription() ? $oEntity->getLongDescription() : $oStammEntity->getLongDescription();
                            $i18n[$lang->abbr][$wawi_field] = $oDesc->value;
                        }
                        if ($field == "oxarticles__oxtags" && !$i18n[$lang->abbr][$wawi_field] && method_exists($oEntity, "getTags") && $oEntity->getTags()) {
                            $oTagCloud = oxNew("oxTagCloud");
                            $aCloudArray = $oTagCloud->getCloudArray();
                            if ($aCloudArray[$oEntity->getTags()]) {
                                $tags = $oTagCloud->getTagTitle($oEntity->getTags());
                                $i18n[$lang->abbr][$wawi_field] = $tags;
                            }
                        }
                    }
                }
            }
        }
        return $i18n;
    }

    public function get_product_field($foreignId, $id, $field)
    {
        $oDb = oxDb::getDb();
        if ($id)
            $oxid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($id));
        if (!$oxid) {
            if ($foreignId)
                $oxid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($foreignId));
        }
        if ($oxid) {
            $oArticle = oxNew("oxarticle");
            $oArticle->load($oxid);
            $field = "oxarticles__" . $field;
            return $oArticle->{$field}->rawValue;
        }
    }

    public function get_products($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " and oa.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxarticles', $page);
        } else {
            return $this->getCount('oxarticles', null, " where oxparentid=''");
        }

        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->_aProductFields['oxcreated'] == 'createdTimestamp')
            unset($this->_aProductFields['oxcreated']);
        $sFields = $this->toRemoteSqlKeys($this->_aProductFields, 'oa', $this->_aProductMultiLangFields);


        $sQ = 'SELECT ' . $sFields . ' FROM oxarticles oa LEFT JOIN oxartextends oae ON oa.oxid = oae.oxid WHERE oxparentid = "" ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();

        foreach ($result as $product) {

            $oArticle = oxNew('oxarticle');
            $oArticle->load($product['id']);
            if ($oArticle->oxarticles__oxpicsgenerated->value == 12) {
                for ($i = 1; $i <= 4; $i++) {
                    $zoomfield = "oxzoom" . $i;
                    $field = "oxpic" . $i;
                    $ormzoomfield = "oxarticles__" . $zoomfield;
                    $ormfield = "oxarticles__" . $field;
                    if ($oArticle->{$ormzoomfield}->value && $oArticle->{$ormzoomfield}->value != "nopic.jpg" && strpos($oArticle->{$ormzoomfield}->value, "nopic.jpg") === FALSE) {
                        //$oDb->execute("update oxarticles set $field = $zoomfield where oxid=".$oDb->quote($oVariant->getId()));
                        $zoompic = $oDb->getOne("select $zoomfield from oxarticles where oxid=" . $oDb->quote($oArticle->getId()));
                        $zoompic = str_replace("z" . $i . "/", "", $zoompic);
                        $oDb->execute("update oxarticles set $zoomfield = 'nopic.jpg' where oxid=" . $oDb->quote($oArticle->getId()));
                        //$v->{$picfield} = $oDb->getOne("select $field from oxarticles where oxid=".$oDb->quote($oVariant->getId()));
                        //if (!$zoompic || $zoompic == "nopic.jpg")
                        {
                            $zoompic = $oArticle->{$ormzoomfield}->value;
                        }
                        $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . basename($zoompic);
                        $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($product['picture' . $i]);
                        if (file_exists($sOldPicturePath)) {
                            @copy($sOldPicturePath, $sNewPicturePath);
                        } else {
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($product['picture' . $i]);
                            if (file_exists($sOldPicturePath)) {
                                @copy($sOldPicturePath, $sNewPicturePath);
                            }
                        }
                        //file_put_contents('log.txt',$sOldPicturePath." ".$oArticle->{$ormzoomfield}->value."\n", FILE_APPEND);
                    } else {
                        $picfield = "oxpic" . $i;
                        $pic = $oDb->getOne("select $picfield from oxarticles where oxid=" . $oDb->quote($oArticle->getId()));
                        if ($pic && $pic != "nopic.jpg") {
                            $pic = str_replace("_p" . $i . ".", ".", $pic);
                            $pinfo = pathinfo($pic);
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . $pinfo['filename'] . "_z" . $i . "." . $pinfo['extension'];
                            $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($product['picture' . $i]);
                            if (file_exists($sOldPicturePath))
                                @copy($sOldPicturePath, $sNewPicturePath);
                            //file_put_contents('log.txt',$sOldPicturePath."\n", FILE_APPEND);
                        }
                    }
                }
                for ($i = 5; $i <= $this->_iPicsAmount; $i++) {
                    $picfield = "oxpic" . $i;
                    $pic = $oDb->getOne("select $picfield from oxarticles where oxid=" . $oDb->quote($oArticle->getId()));
                    if ($pic && $pic != "nopic.jpg") {
                        $zoomfield = "oxzoom" . $i;
                        $ormzoomfield = "oxarticles__" . $zoomfield;
                        if ($oArticle->{$ormzoomfield}->value && $oArticle->{$ormzoomfield}->value != "nopic.jpg" && strpos($oArticle->{$ormzoomfield}->value, "nopic.jpg") === FALSE) {
                            $pic = $oArticle->{$ormzoomfield}->value;
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . basename($pic);
                        } else {
                            $pinfo = pathinfo($pic);
                            $pic = str_replace("_p" . $i . ".", ".", $pic);
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . $pinfo['filename'] . "_z" . $i . "." . $pinfo['extension'];
                        }

                        $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($product['picture' . $i]);
                        if (file_exists($sOldPicturePath))
                            @copy($sOldPicturePath, $sNewPicturePath);
                        else {
                            $pic = $oDb->getOne("select $picfield from oxarticles where oxid=" . $oDb->quote($oArticle->getId()));
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($pic);
                            $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($product['picture' . $i]);
                            if (file_exists($sOldPicturePath)) {
                                @copy($sOldPicturePath, $sNewPicturePath);
                            } else {
                                $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($product['picture' . $i]);
                                if (file_exists($sOldPicturePath)) {
                                    @copy($sOldPicturePath, $sNewPicturePath);
                                }
                            }
                        }
                    }
                }
                $picsgenerated = 0;
                for ($i = 1; $i <= $this->_iPicsAmount; $i++) {
                    $field = "oxarticles__oxpic" . $i;
                    if ($oArticle->{$field}->value && $oArticle->{$field}->value != 'nopic.jpg') {
                        $picsgenerated = $i;
                    }
                }
                $oOldArticle = $oArticle;
                $oDb->execute("update oxarticles set oxpicsgenerated=$picsgenerated where oxid=" . $oDb->quote($oArticle->getId()));
                $oArticle = oxNew("oxarticle");
                $oArticle->load($oOldArticle->getId());
                unset($oOldArticle);
                //var_dump($oArticle->oxarticles__oxpicsgenerated->value);
            }
            //Map pictures
            $pictures = array();

            array_push($pictures, $product['picture1']);
            unset($product['picture1']);
            array_push($pictures, $product['picture2']);
            unset($product['picture2']);
            array_push($pictures, $product['picture3']);
            unset($product['picture3']);
            array_push($pictures, $product['picture4']);
            unset($product['picture4']);
            array_push($pictures, $product['picture5']);
            unset($product['picture5']);
            array_push($pictures, $product['picture6']);
            unset($product['picture6']);
            array_push($pictures, $product['picture7']);
            unset($product['picture7']);
            array_push($pictures, $product['picture8']);
            unset($product['picture8']);
            array_push($pictures, $product['picture9']);
            unset($product['picture9']);
            array_push($pictures, $product['picture10']);
            unset($product['picture10']);
            array_push($pictures, $product['picture11']);
            unset($product['picture11']);
            array_push($pictures, $product['picture12']);
            unset($product['picture12']);
            for ($i = 0; $i < count($pictures); $i++)
                if (strpos($pictures[$i], 'nopic.jpg') !== FALSE)
                    $pictures[$i] = '';
            $product['pictures'] = $pictures;
            $varname = explode(" | ", $oArticle->oxarticles__oxvarname->value);
            $product["variantName"] = $varname[0];
            $product["scaleprices"] = $this->get_scale_prices($oArticle);
            $product["mediafiles"] = $this->get_media_files($oArticle);
            $product["attributes"] = $this->get_attribute_values($oArticle);
            $product["options"] = $this->get_option_values($oArticle);
            $product["categories"] = $this->get_category_values($oArticle);
            if ($oArticle->oxarticles__oxpricea->value)
                $product["pricea"] = $oArticle->oxarticles__oxpricea->value;
            if ($oArticle->oxarticles__oxpriceb->value)
                $product["priceb"] = $oArticle->oxarticles__oxpriceb->value;
            if ($oArticle->oxarticles__oxpricec->value)
                $product["pricec"] = $oArticle->oxarticles__oxpricec->value;
            $product = $this->get_additional_fields('oxarticle', $product, $oArticle);
            $i18n = $this->get_i18n($oArticle, "_aProductMultiLangFields", "oxarticles");
            if (count($i18n) > 0) {
                $product["i18n"] = $i18n;
            }
            $this->resetLanguage($oArticle);

            array_push($aResult, $product);
            $oVariants = $oArticle->getAdminVariants();
            if (count($oVariants) > 0) {
                $realVariants = [];
                foreach ($oVariants as $oVariant) {
                    if (!$oVariant->oxarticles__oxid->value) {
                        $rows = oxDb::getDb()->getAll("select oxid from oxarticles where oxparentid=" . $oDb->quote($oArticle->getId()));
                        foreach ($rows as $row) {
                            $oVariant2 = oxNew("oxarticle");
                            $oVariant2->load($row[0]);
                            $realVariants[] = $oVariant2;
                        }
                        $oVariants = $realVariants;
                        break;
                    }
                }
            }
            if (count($oVariants) > 0) {
                $realVariants = array();
                foreach ($oVariants as $oVariant) {
                    $vrnt = oxNew("oxarticle");
                    $vrnt->load($oVariant->oxarticles__oxid->value);
                    $realVariants[] = $vrnt;
                }
                $oVariants = $realVariants;
                //file_put_contents("log.txt",print_r($aVariants,true));
                $aVariants = array();
                $aVariantTree = array();
                $this->_flattenMdVariants($oVariants, $oArticle, $aVariants, 0, $aVariantTree);

                /*$oVariants = $oArticle->getMdVariants();

					$aVariants = array();
					$aVariants = $this->_flattenMdVariants($oVariants,$oVariants->getMdSubvariants(),array());*/

                $sort = 0;
                $vind = 1;
                $sind = 1;
                foreach ($aVariants as $variant) {
                    $oVariant = oxNew("oxarticle");

                    if ($oVariant->load($variant->id)) {
                        $v = $this->_fillObject($oVariant, $variant, $vind);
                        $vind++;
                    } else {
                        $v = (object)$product;
                        $v->id = $variant->id;
                        $v->active = $variant->active;
                        $v->parent = $variant->parent;
                        $v->title = $variant->title;
                        $v->price = $variant->price;
                        $v->sort = $sort;
                        $v->sku = $oArticle->oxarticles__oxartnum->value . "-sub-" . $sind;
                        $v->variantName = $variant->variantName;
                        $v->longdescription = "";
                        $v->description = "";
                        $sind++;
                        $sort++;
                    }

                    array_push($aResult, $v);

                }

                //file_put_contents("log.txt",print_r($aResult,true));
                /*foreach ($aVariants as $oVariant) {

						$v = (object)$product;
						$v->id = $oVariant->getId();
						$v->parent = $oVariant->getParentId() ? $oVariant->getParentId() : $product['id'];
						$v->title = $oVariant->getName();
						$v->price = $oVariant->getDPrice() ? $oVariant->getDPrice() : $product['price'];
						$v->sort = $sort;
						$v->sku = $oArticle->oxarticles__oxartnum->value."-".$ind;
						$v->variantname = '';
						array_push($aResult,$v);
						$ind++;
						$sort++;
					}*/
            }

        }
        $this->utf8_encode_deep($aResult);

        return $aResult;
    }

    protected function _fillObject(&$oVariant, $variant, $ind)
    {
        $v = new stdClass();
        $oParent = $oVariant->getParentArticle();
        foreach ($this->_aProductFields as $key => $val) {
            $field = "oxarticles__$key";
            $v->{$val} = $oVariant->{$field}->value;
        }
        $oDb = oxDb::getDb();
        if (!$v->longdescription)
            $v->longdescription = oxDb::getDb()->getOne("select oxlongdesc from oxartextends where oxid=" . $oDb->quote($oVariant->oxarticles__oxid->value));

        $v->parent = $variant->parent;
        $v->title = $variant->title;
        $v->icon = str_replace("icon/", "", $v->icon);
        $v->thumb = str_replace("0/", "", $v->thumb);
        $v->variantName = "";
        //$v->sku = $oDb->getOne("select oxartnum from oxarticles where oxid=".$oDb->quote($oVariant->getId()));

        /*if (!$v->sku || true)
			{
				$v->sku = $oParent->oxarticles__oxartnum->value."-".$ind;
                $oDb->execute("update oxarticles set oxartnum = ".$oDb->quote($v->sku)." where oxid=".$oDb->quote($oVariant->getId()));
			}*/
        if ($oVariant->oxarticles__oxpicsgenerated->value == 12) {
            for ($i = 1; $i <= 4; $i++) {
                $zoomfield = "oxzoom" . $i;
                $field = "oxpic" . $i;
                $ormzoomfield = "oxarticles__" . $zoomfield;
                $ormfield = "oxarticles__" . $field;
                if ($oVariant->{$ormzoomfield}->value && $oVariant->{$ormzoomfield}->value != "nopic.jpg" && strpos($oVariant->{$ormzoomfield}->value, "nopic.jpg") === FALSE) {
                    //$oDb->execute("update oxarticles set $field = $zoomfield where oxid=".$oDb->quote($oVariant->getId()));
                    $zoompic = $oDb->getOne("select $zoomfield from oxarticles where oxid=" . $oDb->quote($oVariant->getId()));
                    $zoompic = str_replace("z" . $i . "/", "", $zoompic);
                    $oDb->execute("update oxarticles set $zoomfield = 'nopic.jpg' where oxid=" . $oDb->quote($oVariant->getId()));
                    $picfield = "picture" . $i;
                    $zoompic = $oVariant->{$ormzoomfield}->value;
                    $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . basename($zoompic);

                    $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($v->{$picfield});

                    if (file_exists($sOldPicturePath)) {
                        @copy($sOldPicturePath, $sNewPicturePath);
                    } else {
                        $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($v->{$picfield});
                        if (file_exists($sOldPicturePath)) {
                            @copy($sOldPicturePath, $sNewPicturePath);
                        }
                    }
                } else {
                    $picfield = "picture" . $i;
                    if ($v->{$picfield} && strpos($v->{$picfield}, "nopic.jpg") === FALSE) {
                        $pic = str_replace("_p" . $i . ".", ".", $v->{$picfield});
                        $pinfo = pathinfo($pic);

                        $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . $pinfo['filename'] . "_z" . $i . "." . $pinfo['extension'];
                        $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($v->{$picfield});
                        if (file_exists($sOldPicturePath)) {
                            @copy($sOldPicturePath, $sNewPicturePath);
                        }
                    }
                }
            }
            for ($i = 5; $i <= $this->_iPicsAmount; $i++) {
                $field = "oxpic" . $i;
                $picfield = "picture" . $i;
                if ($v->{$picfield} && strpos($v->{$picfield}, "nopic.jpg") === FALSE) {
                    if ($oVariant->{$ormzoomfield}->value && $oVariant->{$ormzoomfield}->value != "nopic.jpg" && strpos($oVariant->{$ormzoomfield}->value, "nopic.jpg") === FALSE) {
                        $pic = $oVariant->{$ormzoomfield}->value;
                        $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . basename($pic);
                    } else {
                        $pic = str_replace("_p" . $i . ".", ".", $v->{$picfield});
                        $pinfo = pathinfo($pic);
                        $sOldPicturePath = getShopBasePath() . 'out/pictures/z' . $i . '/' . $pinfo['filename'] . "_z" . $i . "." . $pinfo['extension'];
                    }

                    $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($v->{$picfield});
                    if (file_exists($sOldPicturePath)) {
                        @copy($sOldPicturePath, $sNewPicturePath);
                    } else {
                        $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($pic);
                        $sNewPicturePath = getShopBasePath() . 'out/pictures/master/' . $i . '/' . basename($v->{$picfield});

                        if (file_exists($sOldPicturePath)) {
                            @copy($sOldPicturePath, $sNewPicturePath);
                        } else {
                            $sOldPicturePath = getShopBasePath() . 'out/pictures/' . $i . '/' . basename($v->{$picfield});
                            if (file_exists($sOldPicturePath)) {
                                @copy($sOldPicturePath, $sNewPicturePath);
                            }
                        }
                    }
                }
                //file_put_contents('log.txt',$sNewPicturePath."\n",FILE_APPEND);
            }
            $picsgenerated = 0;
            for ($i = 1; $i <= $this->_iPicsAmount; $i++) {
                $field = "oxarticles__oxpic" . $i;
                if ($oVariant->{$field}->value && $oVariant->{$field}->value != 'nopic.jpg') {
                    $picsgenerated = $i;
                }
            }
            $oOldVariant = $oVariant;
            $oDb->execute("update oxarticles set oxpicsgenerated=$picsgenerated where oxid=" . $oDb->quote($oVariant->getId()));
            $oVariant = oxNew("oxarticle");
            $oVariant->load($oOldVariant->getId());
            unset($oOldVariant);
        }
        //Map pictures
        $pictures = array();

        array_push($pictures, str_replace("1/", "", $v->picture1));
        unset($v->picture1);
        array_push($pictures, str_replace("2/", "", $v->picture2));
        unset($v->picture2);
        array_push($pictures, str_replace("3/", "", $v->picture3));
        unset($v->picture3);
        array_push($pictures, str_replace("4/", "", $v->picture4));
        unset($v->picture4);
        array_push($pictures, str_replace("5/", "", $v->picture5));
        unset($v->picture5);
        array_push($pictures, str_replace("6/", "", $v->picture6));
        unset($v->picture6);
        array_push($pictures, str_replace("7/", "", $v->picture7));
        unset($v->picture7);
        array_push($pictures, str_replace("8/", "", $v->picture8));
        unset($v->picture8);
        array_push($pictures, str_replace("9/", "", $v->picture9));
        unset($v->picture9);
        array_push($pictures, str_replace("10/", "", $v->picture10));
        unset($v->picture10);
        array_push($pictures, str_replace("11/", "", $v->picture11));
        unset($v->picture11);
        array_push($pictures, str_replace("12/", "", $v->picture12));
        unset($v->picture12);
        for ($i = 0; $i < count($pictures); $i++)
            if (strpos($pictures[$i], 'nopic.jpg') !== FALSE)
                $pictures[$i] = '';
        $v->pictures = $pictures;
        $v->scaleprices = $this->get_scale_prices($oVariant);
        $v->mediafiles = $this->get_media_files($oVariant);
        $v->attributes = $this->get_attribute_values($oVariant);
        $v->options = $this->get_option_values($oVariant);
        if ($oVariant->oxarticles__oxpricea->value)
            $v->pricea = $oVariant->oxarticles__oxpricea->value;
        if ($oVariant->oxarticles__oxpriceb->value)
            $v->priceb = $oVariant->oxarticles__oxpriceb->value;
        if ($oVariant->oxarticles__oxpricec->value)
            $v->pricec = $oVariant->oxarticles__oxpricec->value;
        $i18n = $this->get_i18n($oVariant, "_aProductMultiLangFields", "oxarticles");
        if (count($i18n) > 0) {
            $v->i18n = $i18n;
        }
        $this->resetLanguage($oVariant);
        return $v;
    }

    public function get_media_file($sFile)
    {
        $sFilePath = getShopBasePath() . 'out/media/' . basename($sFile);
        header('Content-Type: image/jpeg');
        readfile($sFilePath);
        exit();
    }

    public function get_download_file($downloadId)
    {
        $oFile = oxNew("oxfile");
        $oFile->load($downloadId);
        $sFilePath = $oFile->getStoreLocation();
        header('Content-Type: image/jpeg');
        readfile($sFilePath);
        exit();
    }

    protected function _getImageSize($iImgNum, $sImgConf)
    {
        $myConfig = agConfig::getInstance();
        $sSize = false;

        switch ($sImgConf) {
            case 'aDetailImageSizes':
                $aDetailImageSizes = $myConfig->getConfigParam($sImgConf);
                $sSize = $myConfig->getConfigParam('sDetailImageSize');
                if (isset($aDetailImageSizes['oxpic' . $iImgNum])) {
                    $sSize = $aDetailImageSizes['oxpic' . $iImgNum];
                }
                break;
            default:
                $sSize = $myConfig->getConfigParam($sImgConf);
                break;
        }
        if ($sSize) {
            return explode('*', trim($sSize));
        }
    }

    public function get_shop_version()
    {
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        return $sVersion;
    }

    public function prepare_uploaded_pictures($type, $pictures, $data)
    {
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_PREPARE_UPLOADED_PICTURES, array($type, $pictures, $data));
        $oArticle = null;
        $oDb = oxDb::getDb();
        if (version_compare($sVersion, '4.5.0') <= 0) {
            $oUtilsPic = agUtilsPic::getInstance();
            $oPictureHandler = agPictureHandler::getInstance();
            $hasThumb = false;
            $hasIcon = false;
            foreach ($pictures as $picture) {
                if ($picture->sort == "thumb")
                    $hasThumb = true;
                else if ($picture->sort == "icon")
                    $hasIcon = true;
            }
            $oArticle = oxNew("oxarticle");
            $oxid = $oDb->getOne("select from oxarticles where wwforeignid=" . $oDb->quote($data->id));
            if (!$oxid)
                $oxid = $oDb->getOne("select from oxarticles where oxid=" . $oDb->quote($data->foreignId));
            $oArticle->load($oxid);
            if (!$hasThumb && $type == "product") {
                foreach ($pictures as $picture) {
                    if (intval($picture->sort) == 1) {
                        $sPicture = getShopBasePath() . "out/pictures/master/1/" . $picture->pictureName;
                        $sTarget = getShopBasePath() . "out/pictures/0/" . $picture->pictureName;
                        copy($sPicture, $sTarget);
                        $aSize = $this->_getImageSize($picture->sort, 'sThumbnailsize');
                        if ($aSize) {
                            $oUtilsPic->resizeImage($sPicture, $sTarget, $aSize[0], $aSize[1]);
                        }
                        if ($oArticle->getId()) {
                            $oArticle->oxarticles__oxthumb = new oxField($picture->pictureName);
                            $oArticle->save();
                        }
                    }
                }
            }
            if (!$hasIcon && $type == "product") {
                foreach ($pictures as $picture) {
                    if (intval($picture->sort) == 1) {
                        $sPicture = getShopBasePath() . "out/pictures/master/1/" . $picture->pictureName;
                        $sTarget = getShopBasePath() . "out/pictures/icon/" . $picture->pictureName;
                        copy($sPicture, $sTarget);
                        $aSize = $this->_getImageSize($picture->sort, 'sIconsize');
                        if ($aSize) {
                            $oUtilsPic->resizeImage($sPicture, $sTarget, $aSize[0], $aSize[1]);
                        }
                        if ($oArticle->getId()) {
                            $oArticle->oxarticles__oxicon = new oxField($picture->pictureName);
                            $oArticle->save();
                        }
                    }
                }
            }
            foreach ($pictures as $picture) {
                $aSize = null;
                $sPicture = getShopBasePath() . "out/pictures/master/" . $picture->sort . "/" . $picture->pictureName;
                $sTarget = getShopBasePath() . "out/pictures/" . ($picture->sort == "thumb" ? "0" : $picture->sort) . "/" . $picture->pictureName;
                if ($picture->sort == "thumb") {
                    if ($type == "product")
                        $aSize = $this->_getImageSize($picture->sort, 'sThumbnailsize');
                    else if ($type == "category")
                        $aSize = $this->_getImageSize($picture->sort, 'sCatThumbnailsize');
                } else if ($picture->sort == "icon") {
                    $aSize = $this->_getImageSize($picture->sort, 'sIconsize');
                } else if (intval($picture->sort) > 0) {
                    $aSize = $this->_getImageSize($picture->sort, 'aDetailImageSizes');
                    $oUtilsPic->resizeImage($sPicture, $sTarget, $aSize[0], $aSize[1]);
                    if (!$oArticle) {
                        $oDb = oxDb::getDb();
                        $oArticle = oxNew("oxarticle");
                        $blExists = $oArticle->load($data->foreignId);

                        if (!$blExists) {
                            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
                            if ($sOxid) {
                                $blExists = $oArticle->load($sOxid);
                            }
                        }
                    }
                    if (intval($picture->sort) == 1) {
                        if (!$oDb->getOne('select oxicon from oxarticles where oxid=' . $oDb->quote($oArticle->getId())) && ($aIconSize = $this->_getImageSize(1, 'sIconsize'))) {
                            //$sIconTarget = dirname($sTarget) . '/' . $oPictureHandler->getIconName( $sTarget );
                            //$oUtilsPic->resizeImage( $sPicture, $sIconTarget, $aIconSize[0], $aIconSize[1] );
                            $sIconTarget = getShopBasePath() . "out/pictures/icon/" . $oPictureHandler->getIconName($sTarget);
                            $oUtilsPic->resizeImage($sPicture, $sIconTarget, $aIconSize[0], $aIconSize[1]);
                        }
                        if (!$oDb->getOne('select oxthumb from oxarticles where oxid=' . $oDb->quote($oArticle->getId())) && ($aThumbSize = $this->_getImageSize(1, 'sThumbnailsize'))) {
                            //$sIconTarget = dirname($sTarget) . '/' . $oPictureHandler->getIconName( $sTarget );
                            //$oUtilsPic->resizeImage( $sPicture, $sIconTarget, $aIconSize[0], $aIconSize[1] );

                            $sThumbTarget = getShopBasePath() . "out/pictures/0/" . $oPictureHandler->getThumbName($sTarget);
                            $oUtilsPic->resizeImage($sPicture, $sThumbTarget, $aThumbSize[0], $aThumbSize[1]);
                        }
                    }
                    if ($picture->sort < 5) {
                        if (($aZoomSize = $this->_getImageSize(1, 'sZoomImageSize'))) {
                            $sZoomTarget = getShopBasePath() . "out/pictures/z" . $picture->sort . "/" . $oPictureHandler->getZoomName($sTarget, $picture->sort);
                            $oUtilsPic->resizeImage($sPicture, $sZoomTarget, $aZoomSize[0], $aZoomSize[1]);
                        }
                    }
                    continue;
                    /*if (!$oArticle)
                        {
                            $oDb = oxDb::getDb();
                            $oArticle = oxNew("oxarticle");
                            $blExists = $oArticle->load($data->foreignId);

                            if(!$blExists){
                                $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
                                if($sOxid){
                                    $blExists = $oArticle->load($sOxid);
                                }
                            }
                        }
                        $oPictureHandler->generateArticlePictures( $oArticle, $picture->sort );
                        continue;*/
                }
                if ($aSize) {
                    $oUtilsPic->resizeImage($sPicture, $sTarget, $aSize[0], $aSize[1]);
                }
            }
        } else {
            $sDefaultImageQuality = $conf->getShopConfVar("sDefaultImageQuality");
            foreach ($pictures as $picture) {
                if ($picture->sort == "thumb") {
                    if ($type == "product")
                        $aSize = $this->_getImageSize($picture->sort, 'sThumbnailsize');
                    else if ($type == "category")
                        $aSize = $this->_getImageSize($picture->sort, 'sCatThumbnailsize');
                } else if ($picture->sort == "icon") {
                    $aSize = $this->_getImageSize($picture->sort, 'sIconsize');
                } else
                    $aSize = $this->_getImageSize($picture->sort, 'aDetailImageSizes');
                $sMasterPicture = getShopBasePath() . "out/pictures/master/product/" . $picture->sort . "/" . $picture->pictureName;
                if (file_exists($sMasterPicture)) {
                    $sGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . $picture->pictureName;
                    if (file_exists($sGenPicture) && filemtime($sGenPicture) < filemtime($sMasterPicture)) {
                        $info = pathinfo($sGenPicture);
                        @unlink($sGenPicture);
                        $webpGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . basename($picture->pictureName, "." . $info["extension"]) . ".webp";
                        if (file_exists($webpGenPicture))
                            @unlink($webpGenPicture);
                        $webpGenPicture = getShopBasePath() . "out/pictures/master/product/" . $picture->sort . "/" . basename($picture->pictureName, "." . $info["extension"]) . ".webp";
                        if (file_exists($webpGenPicture))
                            @unlink($webpGenPicture);
                    }
                    if (intval($picture->sort) > 0) {
                        $aSize = $this->_getImageSize("icon", 'sIconsize');
                        $sGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . $picture->pictureName;
                        if (file_exists($sGenPicture) && filemtime($sGenPicture) < filemtime($sMasterPicture)) {
                            $info = pathinfo($sGenPicture);
                            @unlink($sGenPicture);
                            $webpGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . basename($picture->pictureName, "." . $info["extension"]) . ".webp";
                            if (file_exists($webpGenPicture))
                                @unlink($webpGenPicture);
                        }
                        $aSize = $this->_getImageSize("icon", 'sThumbnailsize');
                        $sGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . $picture->pictureName;
                        if (file_exists($sGenPicture) && filemtime($sGenPicture) < filemtime($sMasterPicture)) {
                            $info = pathinfo($sGenPicture);
                            @unlink($sGenPicture);
                            $webpGenPicture = getShopBasePath() . "out/pictures/generated/product/" . $picture->sort . "/" . $aSize[0] . "_" . $aSize[1] . "_" . $sDefaultImageQuality . "/" . basename($picture->pictureName, "." . $info["extension"]) . ".webp";
                            if (file_exists($webpGenPicture))
                                @unlink($webpGenPicture);
                        }
                    }
                }
            }
        }
    }

    protected function prepare_pic($pic)
    {
        $pos = strpos($pic, "/");
        if ($pos !== FALSE)
            $pic = substr($pic, $pos + 1);
        return $pic;
    }

    public function get_picture($sType, $sPicture, $iIndex = 1, $blHash = false, $entity = null, $returnPath = false)
    {
        //Sanitize vars
        $iIndex = ($iIndex === 'icon' || $iIndex === 'thumb') ? $iIndex : intval($iIndex);

        $aAllowedTypes = array('product', 'category', 'manufacturer', 'vendor', 'productoption');
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        $iVersion = intval(str_replace('.', '', $sVersion));
        $sPicPathDir = "";
        if (in_array($sType, $aAllowedTypes)) {
            if (version_compare($sVersion, '4.5.1') >= 0) {
                $sPicPathDir = getShopBasePath() . 'out/pictures/master/' . $sType . '/' . $iIndex . '/';
                $sPicturePath = $sPicPathDir . basename($sPicture);
            } else {
                if ($sType == "product") {
                    if ($iIndex == "thumb")
                        $iIndex = 0;
                    else if ($iIndex == "icon")
                        $iIndex = "icon";
                } else {
                    if ($sType == "manufacturer")
                        $iIndex = "icon";
                    else {
                        if ($iIndex == "thumb")
                            $iIndex = 0;
                        else if ($iIndex == "icon")
                            $iIndex = "icon";
                    }
                }

                if (is_numeric($iIndex) && $iIndex > 0) {
                    $sPicPathDir = getShopBasePath() . 'out/pictures/master/' . $iIndex . '/';
                    $sPicturePath = $sPicPathDir . basename($sPicture);
                } else {
                    $sPicPathDir = getShopBasePath() . 'out/pictures/' . $iIndex . '/';
                    $sPicturePath = $sPicPathDir . basename($sPicture);
                }
            }
        } else {
            exit();
        }
        if (!file_exists($sPicturePath)) {
            if ($sType == "product") {
                $oProduct = oxNew("oxarticle");
                $oProduct->load($entity);
                if ($iIndex == "icon")
                    $field = "oxarticles__oxicon";
                else if ($iIndex == "thumb")
                    $field = "oxarticles__oxthumb";
                else
                    $field = "oxarticles__oxpic" . $iIndex;


                $sPicturePath = $sPicPathDir . $this->prepare_pic($oProduct->{$field}->value);
            } else if ($sType == "category") {
                $oProduct = oxNew("oxcategory");
                $oProduct->load($entity);
                if ($iIndex == "icon")
                    $field = "oxcategories__oxicon";
                else if ($iIndex == "thumb")
                    $field = "oxcategories__oxthumb";
                $sPicturePath = $sPicPathDir . $this->prepare_pic($oProduct->{$field}->value);
            } else if ($sType == "manufacturer") {
                $oProduct = oxNew("oxmanufacturer");
                $oProduct->load($entity);
                if ($iIndex == "icon")
                    $field = "oxmanufacturers__oxicon";

                $sPicturePath = $sPicPathDir . $this->prepare_pic($oProduct->{$field}->value);
            }
        }
        //file_put_contents("log.txt",$sPicturePath);
        if (file_exists($sPicturePath)) {
            if ($blHash) {
                return sha1_file($sPicturePath);
            } else {
                if ($returnPath) {
                    return str_replace(getShopBasePath(), '', $sPicturePath);
                } else {
                    header('Content-Type: image/jpeg');
                    readfile($sPicturePath);
                    exit();
                }
            }
        } else
            file_put_contents("log.txt", "not found: " . $sPicturePath . "\n", FILE_APPEND);
    }

    /*protected function _flattenMdVariants($oParent,$oVariants,$aVariants){
			if($oVariants){
				foreach($oVariants as $oVariant){
					array_push($aVariants,$oVariant);
					$aVariants = $this->_flattenMdVariants($oVariant,$oVariant->getMdSubvariants(),$aVariants);
				}
			}
			return $aVariants;
		}*/

    protected function get_scale_prices($oArticle)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $rows = $oDb->getAll("select * from oxprice2article where oxartid=" . $oDb->quote($oArticle->getId()));
        $scalePrices = array();
        foreach ($rows as $row) {
            $scalePrice = array();
            $scalePrice["amountFrom"] = $row["OXAMOUNT"];
            $scalePrice["amountTo"] = $row["OXAMOUNTTO"];
            $scalePrice["price"] = doubleval($row["OXADDABS"]) != 0 ? $row["OXADDABS"] : $row["OXADDPERC"];
            $scalePrice["priceType"] = doubleval($row["OXADDABS"]) != 0 ? "0" : "1";
            $scalePrices[] = $scalePrice;
        }
        return $scalePrices;
    }

    protected function get_media_files($oArticle)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $rows = $oDb->getAll("select * from oxmediaurls where oxobjectid=" . $oDb->quote($oArticle->getId()));
        $mediaFiles = array();
        foreach ($rows as $row) {
            $mediaFile = array();
            $mediaFile["mediaUrl"] = $row["OXISUPLOADED"] ? null : $row["OXURL"];
            $mediaFile["fileName"] = $row["OXISUPLOADED"] ? $row["OXURL"] : null;
            $mediaFile["description"] = $row["OXDESC"];
            foreach ($this->_getLanguageArray() as $lang) {
                if (!$lang->selected) {
                    $mediaFile["i18n"][$lang->abbr]["description"] = $row["OXDESC_" . $lang->id] ? $row["OXDESC_" . $lang->id] : $row["OXDESC"];
                }
            }
            $mediaFiles[] = $mediaFile;
        }
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        $iVersion = intval(str_replace('.', '', $sVersion));
        if (version_compare($sVersion, '4.7.0') >= 0) {
            $rows = $oDb->getAll("select * from oxfiles where oxartid=" . $oDb->quote($oArticle->getId()));
            foreach ($rows as $row) {
                $mediaFile = array();
                $mediaFile["mediaUrl"] = null;
                $mediaFile["fileName"] = $row["OXFILENAME"];
                $mediaFile["description"] = null;
                $mediaFile["fileType"] = "download";
                $mediaFile["foreignId"] = $row["OXID"];
                $mediaFiles[] = $mediaFile;
            }
        }
        return $mediaFiles;
    }

    protected function _flattenMdVariants($oVariants, $oArticle, &$arr, $i, &$aVariantTree)
    {
        $varr = explode(" | ", $oArticle->oxarticles__oxvarname->value);
        $vcnt = count($varr);

        if ($oVariants && $vcnt > $i) {

            $localVariantTree = array();
            foreach ($oVariants as $oVariant) {
                $varname = explode(" | ", $oVariant->oxarticles__oxvarselect->value);
                if (count($varname) != $vcnt)
                    continue;
                if (count($varname) == 0)
                    $varname = array($oVariant->oxarticles__oxvarselect->value);

                $varname = array_slice($varname, 0, $i + 1);
                $varname = implode(" | ", $varname);
                if ($vcnt == $i + 1) {
                    $aVariantTree[$varname] = $localVariantTree[$varname] = $oVariant;
                } else {
                    $aVariantTree[$varname] = $localVariantTree[$varname] = md5($oArticle->getId() . $varname . $i);

                }
            }
            foreach ($localVariantTree as $key => $val) {
                $v = new stdClass();
                $varname = explode(" | ", $key);
                if ($i == 0)
                    $v->title = $key;
                else
                    $v->title = end($varname);
                if ($vcnt == $i + 1)
                    $v->price = $val->oxarticles__oxprice->value;
                else
                    $v->price = $oArticle->oxarticles__oxvarminprice->value;
                if ($vcnt == $i + 1) {
                    $v->id = $val->getId();

                } else {
                    $v->id = $val;
                    $v->active = true;
                    $v->variantName = $varr[$i + 1];
                }
                if ($i == 0)
                    $v->parent = $oArticle->getId();
                else {
                    $v->parent = $aVariantTree[implode(" | ", count($varname) > 1 ? array_slice($varname, 0, count($varname) - 1) : $varname)];
                }

                $arr[] = $v;
            }
            $this->_flattenMdVariants($oVariants, $oArticle, $arr, $i + 1, $aVariantTree);
        }
        return;
    }

    public function set_products($data)
    {
        $this->utf8_decode_deep($data);
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_SET_PRODUCTS, array($data));
        foreach ($data as $value) {
            $this->add_product($value);
        }
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_SET_PRODUCTS, array($data));
        $this->_clearSeoCache();
        $this->_clearCatCache();
    }

    public function set_products_stock($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->set_product_stock($value);
        }

    }

    public function set_products_prices($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->set_product_price($value);
        }

    }

    public function remove_products($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_product($value);
        }

    }

    public function set_attributes($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_attribute($value);
        }

    }

    public function remove_attributes($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_attribute($value);
        }

    }

    public function get_product_accessories($id, $foreignId)
    {
        $oArticle = oxNew('oxarticle');
        $blExists = false;
        if ($foreignId)
            $blExists = $oArticle->load($foreignId);
        if (!$blExists) {
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid)
                $blExists = $oArticle->load($sOxid);
        }
        if ($blExists) {
            $oDb = oxDb::getDb();
            $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
            $result = array();
            $rows = $oDb->getAll("select * from oxaccessoire2article where oxarticlenid=" . $oDb->quote($oArticle->getId()));
            foreach ($rows as $row) {
                if (!$row["OXOBJECTID"])
                    continue;
                $value = array();
                $value["id"] = $row["OXOBJECTID"];
                $value["foreignId"] = $oDb->getOne("select WWFOREIGNID from oxarticles WHERE oxid=" . $oDb->quote($row["OXOBJECTID"]));

                $result[] = $value;
            }
            return $result;
        }
    }

    public function add_product($data)
    {
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_ADD_PRODUCT, array($data));
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        $iVersion = intval(str_replace('.', '', $sVersion));
        $oArticle = oxNew('oxarticle');
        $iLangId = null;
        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oArticle->setLanguage($iLangId);
        }
        if ($id)
            $blExists = $oArticle->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oArticle->load($sOxid);
                if (!$blExists) {
                    $rows = $oDb->getAll('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
                    foreach ($rows as $row) {
                        if ($row['oxid']) {
                            $blExists = $oArticle->load($row['oxid']);
                            if ($blExists)
                                break;
                        }

                    }
                }
            }
        }
        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;
        $aAssign['wwforeignparentid'] = $data->parent_id;
        foreach ($this->_aProductFields as $sLocalKey => $sRemoteKey) {
            if (/*isset($data->$sRemoteKey) &&*/ $sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid' && $sLocalKey != 'wwforeignparentid'
                && strpos($sLocalKey, "oxpic") === FALSE && strpos($sLocalKey, "oxicon") === FALSE && strpos($sLocalKey, "oxthumb") === FALSE && !is_object($data->$sRemoteKey)) {
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
                if ($data->$sRemoteKey === NULL) {
                    foreach (get_object_vars($data) as $key => $val) {
                        if (strtolower($key) == strtolower($sRemoteKey)) {
                            $aAssign[$sLocalKey] = $val;
                            break;
                        }
                    }
                }
            } else if ($data->$sRemoteKey->date) {
                $aAssign[$sLocalKey] = $data->$sRemoteKey->date;
            }
        }


        //Check which parent to use here, wawi is always master
        if ($data->parent_id) {
            $sQ = 'SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->parent_id);
            $sParentId = $oDb->GetOne($sQ);
            $aAssign['oxparentid'] = $sParentId;
            if (!$data->lang || true) {
                foreach ($this->_aSavedProductFields as $field) {
                    $aAssign[$field] = $data->{$field};
                }
                foreach ($this->_aSavedVariantProductFields as $field) {
                    $aAssign[$field] = $data->{$field};
                }
            }

        } else {
            $aAssign['oxparentid'] = null;
            foreach ($this->_aSavedProductFields as $field) {
                unset($aAssign[$field]);
            }
        }
        $oArticle->assign($aAssign);

        if ($data->parent_id) {
            $oArticle->oxarticles__oxvarselect = new oxField($data->title);
            $sQ = 'SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->parent_id);
            $sParentId = $oDb->GetOne($sQ);
            $oParent = oxNew("oxarticle");
            if ($iLangId !== null)
                $oParent->setLanguage($iLangId);
            $oParent->load($sParentId);
            if ($oParent->oxarticles__oxid->value && !$oParent->oxarticles__oxactive->value)
                $oArticle->oxarticles__oxactive = new oxField(0);
            $oArticle->oxarticles__oxtitle = new oxField($oParent->oxarticles__oxtitle->rawValue);
        } else {
            $titlefield = $this->_aProductFields['oxtitle'];
            if ($titlefield && $data->$titlefield)
                $oArticle->oxarticles__oxtitle = new oxField($data->$titlefield);
            else
                $oArticle->oxarticles__oxtitle = new oxField($data->title);
            $oArticle->oxarticles__oxvarselect = new oxField();
        }
        $oArticle->setArticleLongDesc($aAssign['oxlongdesc'] ? $aAssign['oxlongdesc'] : "");

        if (!$blExists) {
            //Set defaults for new entries
            if ($data->oxissearch === NULL && !$data->lang) {
                $oArticle->oxarticles__oxissearch = new oxField(1);
            }
            if (!$oArticle->oxarticles__oxstockflag->value)
                $oArticle->oxarticles__oxstockflag = new oxField(1);

            $oArticle->setId(null);
            if ($conf->getConfigParam('wawiNotReplaceVariantIds') && $data->foreignId) {
                if (!$oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($data->foreignId)))
                    $oArticle->setId($data->foreignId);
            } else {
                $md5 = str_replace("-", "", $data->id);
                if (!$oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($md5)))
                    $oArticle->setId($md5);
                if ($data->foreignId && $md5) {
                    $oDb->execute("update oxaccessoire2article set oxarticlenid=" . $oDb->quote($md5) . " where oxarticlenid=" . $oDb->quote($data->foreignId));
                    $oDb->execute("update oxaccessoire2article set oxobjectid=" . $oDb->quote($md5) . " where oxobjectid=" . $oDb->quote($data->foreignId));

                    $oDb->execute("update oxobject2article set oxarticlenid=" . $oDb->quote($md5) . " where oxarticlenid=" . $oDb->quote($data->foreignId));
                    $oDb->execute("update oxobject2article set oxobjectid=" . $oDb->quote($md5) . " where oxobjectid=" . $oDb->quote($data->foreignId));
                }
            }
        } else if (!$data->lang) {
            if ($data->variants && count($this->_aSavedProductFields) > 0) {
                for ($i = 0; $i < count($data->variants); $i++) {
                    $oVariant = oxNew("oxarticle");
                    if (!$oVariant->load(str_replace("-", "", $data->variants[$i]->id)))
                        if ($data->variants[$i]->foreignId)
                            $oVariant->load($data->variants[$i]->foreignId);

                    if ($oVariant->oxarticles__oxid->value) {
                        foreach ($this->_aSavedProductFields as $field) {
                            $oxfield = 'oxarticles__' . $field;
                            $data->variants[$i]->{$field} = $oVariant->{$oxfield}->value;
                        }
                    }
                }
            }
            if ($oArticle->getId()) {
                $variantIds = $oDb->getAll("select oxid from oxarticles where oxparentid=" . $oDb->quote($oArticle->getId()));
                if (count($variantIds) > 0 && count($this->_getLanguageArray()) >= 3)
                    try {
                        $ids = array();
                        foreach ($variantIds as $row) {
                            $ids[] = $oDb->quote($row['oxid'] ? $row['oxid'] : $row[0]);
                        }
                        $oDb->execute("delete from oxarticles_set1 where oxid in (" . implode(",", $ids) . ")");
                        $oDb->execute("delete from oxartextends_set1 where oxid in (" . implode(",", $ids) . ")");
                    } catch (Exception $ex) {

                    }
                $oDb->execute("delete from oxarticles where oxparentid=" . $oDb->quote($oArticle->getId()));

            }
        } else if ($data->lang && $data->variants && count($this->_aSavedProductFields) > 0) {

            for ($i = 0; $i < count($data->variants); $i++) {
                $oVariant = oxNew("oxarticle");
                if (!$oVariant->load(str_replace("-", "", $data->variants[$i]->id)))
                    if ($data->variants[$i]->foreignId)
                        $oVariant->load($data->variants[$i]->foreignId);

                if ($oVariant->oxarticles__oxid->value) {
                    foreach ($this->_aSavedProductFields as $field) {
                        $oxfield = 'oxarticles__' . $field;
                        $data->variants[$i]->{$field} = $oVariant->{$oxfield}->value;
                    }
                }
            }
        }


        if ($data->manufacturer) {
            $manufid = $oDb->getOne("select oxid from oxmanufacturers where wwforeignid=" . $oDb->quote($data->manufacturer->id));
            if (!$manufid)
                $manufid = $oDb->getOne("select oxid from oxmanufacturers where oxid=" . $oDb->quote($data->manufacturer->foreignId));
        } else
            $manufid = null;
        $oArticle->oxarticles__oxmanufacturerid = new oxField($manufid);
        if ($data->vendor) {
            $vendorid = $oDb->getOne("select oxid from oxvendor where wwforeignid=" . $oDb->quote($data->vendor->id));
            if (!$vendorid)
                $vendorid = $oDb->getOne("select oxid from oxvendor where oxid=" . $oDb->quote($data->vendor->foreignId));
        } else
            $vendorid = null;
        $oArticle->oxarticles__oxvendorid = new oxField($vendorid);

        try {
            agArticleUtils::saveTags($oArticle, $oArticle->oxarticles__oxtags->value);
        } catch (\Exception $ex) {

        }
        try {
            if ($data->tags) {
                $oDb->execute("update oxartextends set oetags" . ($iLangId ? "_" . $iLangId : '') . " = " . $oDb->quote($data->tags) . " where oxid=" . $oDb->quote($oArticle->oxarticles__oxid->value));
            }
        } catch (\Exception $ex) {

        }
        if ($oArticle->oxarticles__oxactivefrom->value && $oArticle->oxarticles__oxactiveto->value && $oArticle->oxarticles__oxactivefrom->value > '2004-01-01 00:00:00' && $oArticle->oxarticles__oxactiveto->value > '2004-01-01 00:00:00')
            $oArticle->oxarticles__oxactive = new oxField(0);
        $oArticle->save();
        $variant_tree = array();
        $variant_ids = array();

        if ($data->variants) {
            foreach ($data->variants as $variant) {
                $variant_parent_ids[$variant->parent_id] = $variant->parent_id;
                $variant_ids[$variant->id] = $variant;
            }
            $end_variants = array();
            foreach ($data->variants as $variant) {
                if (!$variant_parent_ids[$variant->id])
                    $end_variants[] = $variant;
            }

            $varname = $this->build_multivariants($end_variants, $data, $oArticle, $variant_ids);
            if (strpos($varname, "|") !== FALSE) {
                $oArticle->oxarticles__oxvarname = new oxField($varname);
                $oArticle->save();
            }
        }
        if (!$data->lang)
            $oDb->execute("delete from oxprice2article where oxartid=" . $oDb->quote($oArticle->getId()));
        if (!$data->lang && $data->scaleprices) {
            foreach ($data->scaleprices as $scaleprice) {
                $oPrice2Article = oxNew("oxbase");
                $oPrice2Article->init("oxprice2article");
                $oPrice2Article->oxprice2article__oxartid = new oxField($oArticle->getId());
                $oPrice2Article->oxprice2article__oxshopid = new oxField($conf->getShopId());
                $oPrice2Article->oxprice2article__oxamount = new oxField($scaleprice->amountFrom);
                $oPrice2Article->oxprice2article__oxamountto = new oxField($scaleprice->amountTo);

                $oGroup = oxNew('oxgroups');
                if (!$scaleprice->customerGroupForeignId || !$oGroup->load($scaleprice->customerGroupForeignId)) {

                    $sGroupOxid = null;

                    if ($scaleprice->customerGroupId) {
                        $sGroupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups  WHERE WWFOREIGNID = ' . $oDb->quote($scaleprice->customerGroupId));
                        if ($sGroupOxid) {
                            $oGroup->load($sGroupOxid);
                        } else
                            continue;
                    }

                }
                $oPrice2Article->oxprice2article__oxgroupsid = new oxField($oGroup->getId());


                if (intval($scaleprice->priceType) == 0) {
                    $oPrice2Article->oxprice2article__oxaddabs = new oxField($scaleprice->price);
                } else {
                    $oPrice2Article->oxprice2article__oxaddperc = new oxField($scaleprice->price);
                }
                $oPrice2Article->save();
            }
        }
        if (!$conf->getConfigParam('wawiNotExportGroupPrices')) {
            if (!$data->lang)
                $oDb->execute("delete from oxgroup2price where oxarticleid=" . $oDb->quote($oArticle->getId()));
            if (!$data->lang && $data->groupprices) {
                foreach ($data->groupprices as $groupprice) {
                    //Load group by local id or foreign id if needed
                    $oGroup = oxNew('oxgroups');
                    if (!$groupprice->customerGroupForeignId || !$oGroup->load($groupprice->customerGroupForeignId)) {

                        $sGroupOxid = null;

                        if ($groupprice->customerGroupId) {
                            $sGroupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups  WHERE WWFOREIGNID = ' . $oDb->quote($groupprice->customerGroupId));
                        }

                        if ($sGroupOxid) {
                            $oGroup->load($sGroupOxid);
                        } else {
                            continue;
                        }

                    }

                    $oGroup2Price = oxNew("oxbase");
                    $oGroup2Price->init("oxgroup2price");
                    $oGroup2Price->oxgroup2price__oxarticleid = new oxField($oArticle->getId());
                    $oGroup2Price->oxgroup2price__oxshopid = new oxField($conf->getShopId());
                    $oGroup2Price->oxgroup2price__oxgroupid = new oxField($oGroup->getId());
                    $oGroup2Price->oxgroup2price__oxprice = new oxField($groupprice->price);
                    $oGroup2Price->oxgroup2price__oxsort = new oxField($groupprice->sort);
                    $oGroup2Price->save();


                }

            }
        }
        $savedMediaUrls = null;
        if (!$data->lang) {
            if ($this->_aSavedProductMediaFields) {
                $savedMediaUrls = array();

                foreach ($this->_aSavedProductMediaFields as $mfield) {
                    $fld = "oxmediaurls__" . $mfield;
                    foreach ($oArticle->getMediaUrls() as $mediaUrl) {
                        $savedMediaUrls[$mediaUrl->oxmediaurls__oxurl->value][$mfield] = $mediaUrl->{$fld}->value;
                    }
                }

            }
            $oDb->execute("delete from oxmediaurls where oxobjectid=" . $oDb->quote($oArticle->getId()));
            $oDb->execute("delete from oxfiles where oxartid=" . $oDb->quote($oArticle->getId()));
        }

        if (!$data->lang && $data->mediafiles) {
            $hasDownload = false;
            foreach ($data->mediafiles as $mediafile) {
                if ($mediafile->fileType == "download") {
                    $hasDownload = true;
                }
            }
            if (!$hasDownload) {
                $oArticle->oxarticles__oxisdownloadable = new oxField(0);
                $oArticle->save();
            }
            $mediaind = 100;
            foreach ($data->mediafiles as $mediafile) {
                if ($mediafile->fileType != "download") {
                    $oMediaUrls = oxNew("oxMediaUrl");
                    $mediaFileName = $mediafile->fileName ? $mediafile->fileName : $mediafile->mediaUrl;
                    if ($data->lang) {
                        $oMediaUrls->load($oDb->getOne("select oxid from oxmediaurls where oxobjectid=" . $oDb->quote($oArticle->getId()) . " and oxurl=" . $oDb->quote($mediaFileName)));
                        $oMediaUrls->setLanguage($iLangId);
                    }
                    $oMediaUrls->oxmediaurls__oxobjectid = new oxField($oArticle->getId());
                    $oMediaUrls->oxmediaurls__oxurl = new oxField($mediaFileName);
                    $oMediaUrls->oxmediaurls__oxisuploaded = new oxField($mediafile->fileName ? 1 : 0);
                    $oMediaUrls->oxmediaurls__oxdesc = new oxField($mediafile->description);
                    file_put_contents("media.log", print_r(date('Y-m-d H:i:s', $mediafile->lastUpdated), true) . "\n", FILE_APPEND);
                    if ($mediafile->lastUpdated)
                        $oMediaUrls->oxmediaurls__wwuploaddate = new oxField(date('Y-m-d H:i:s', $mediafile->lastUpdated) . "." . $mediaind);
                    if ($savedMediaUrls && $this->_aSavedProductMediaFields) {
                        foreach ($this->_aSavedProductMediaFields as $mfield) {
                            $fld = "oxmediaurls__" . $mfield;
                            $oMediaUrls->{$fld} = new oxField($savedMediaUrls[$oMediaUrls->oxmediaurls__oxurl->value][$mfield]);
                        }
                    }
                    $oMediaUrls->save();
                    $mediaind++;
                } else if (version_compare($sVersion, '4.7.0') >= 0) {
                    $oFile = oxNew("oxfile");
                    $oFile->oxfiles__oxfilename = new oxField($mediafile->fileName);
                    $oFile->oxfiles__oxstorehash = new oxField($mediafile->storeHash);
                    $oFile->oxfiles__oxartid = new oxField($oArticle->getId());
                    $oArticle->oxarticles__oxisdownloadable = new oxField(1);
                    $oArticle->save();
                    if (floatval($oArticle->oxarticles__oxprice->value) == 0.0 && $oArticle->oxarticles__oxnonmaterial->value)
                        $oFile->oxfiles__oxpurchasedonly = new oxField(0);
                    else
                        $oFile->oxfiles__oxpurchasedonly = new oxField(1);
                    $oFile->setId(str_replace('-', '', $mediafile->id));
                    $oFile->save();
                }
            }

        }
        if ($data->lang && $data->mediafiles) {
            $mediaind = 100;
            foreach ($data->mediafiles as $mediafile) {
                if ($mediafile->fileType != "download") {
                    $oMediaUrls = oxNew("oxMediaUrl");
                    $mediaFileName = $mediafile->fileName ? $mediafile->fileName : $mediafile->mediaUrl;
                    if ($oMediaUrls->load($oDb->getOne("select oxid from oxmediaurls where oxobjectid=" . $oDb->quote($oArticle->getId()) . " and oxurl=" . $oDb->quote($mediaFileName)))) {
                        $oMediaUrls->setLanguage($iLangId);
                        $oMediaUrls->oxmediaurls__oxdesc = new oxField($mediafile->description);
                        if ($mediafile->lastUpdated)
                            $oMediaUrls->oxmediaurls__wwuploaddate = new oxField(date('Y-m-d H:i:s', $mediafile->lastUpdated) . "." . $mediaind);
                        $mediaind++;
                        $oMediaUrls->save();
                    }
                }
            }
        }
        if (!$data->lang)
            $oDb->execute("delete from oxobject2attribute where oxobjectid=" . $oDb->quote($oArticle->getId()));
        if ($data->attributes) {
            foreach ($data->attributes as $attribute) {
                $oObject2Attribute = oxNew("oxI18n");
                $attrid = $oDb->getOne("select oxid from oxattribute where wwforeignid=" . $oDb->quote($attribute->attribute_id));
                if (!$attrid)
                    $attrid = $oDb->getOne("select oxid from oxattribute where oxid=" . $oDb->quote($attribute->foreign_attribute_id));
                $oObject2Attribute->init("oxobject2attribute");
                if ($data->lang) {
                    $oObject2Attribute->setLanguage($iLangId);
                    $oObject2Attribute->load($oDb->getOne("select oxid from oxobject2attribute where oxobjectid=" . $oDb->quote($oArticle->getId()) . " and oxattrid=" . $oDb->quote($attrid)));
                }
                $oObject2Attribute->oxobject2attribute__oxobjectid = new oxField($oArticle->getId());
                $oObject2Attribute->oxobject2attribute__oxvalue = new oxField($attribute->value);
                if ($attrid) {
                    $oObject2Attribute->oxobject2attribute__oxattrid = new oxField($attrid);
                    $oObject2Attribute->save();
                    if (!$conf->getConfigParam('bWAWINotAssignAttributesToCategories')) {
                        $excluded = $conf->getConfigParam('aWAWIExcludedAttributesToCategories');
                        if ($excluded && ($excluded[$attribute->attribute_id] || $excluded[$attrid]))
                            continue;
                        $catIds = $oArticle->getCategoryIds();
                        foreach ($catIds as $catId) {
                            if (!$oDb->getOne("select oxid from oxcategory2attribute where oxobjectid=" . $oDb->quote($catId) . " and oxattrid=" . $oDb->quote($attrid)))
                                $oDb->execute("insert into oxcategory2attribute (oxid, oxobjectid, oxattrid) values (" .
                                    $oDb->quote(md5($catId . $attrid . "agwawi")) . "," .
                                    $oDb->quote($catId) . "," .
                                    $oDb->quote($attrid) . ")");
                        }
                    }
                }
            }
        }
        if (!$data->lang)
            $oDb->execute("delete from oxobject2selectlist where oxobjectid=" . $oDb->quote($oArticle->getId()));
        if (!$data->lang && $data->options) {
            $aAssign = array();
            foreach ($data->options as $option) {
                $oObject2SelectList = oxNew("oxbase");
                $oObject2SelectList->init("oxobject2selectlist");
                $aAssign["oxselnid"] = $oDb->getOne("select oxid from oxselectlist where wwforeignid=" . $oDb->quote($option->id));
                if (!$aAssign["oxselnid"])
                    $aAssign["oxselnid"] = $oDb->getOne("select oxid from oxselectlist where oxid=" . $oDb->quote($option->foreignId));
                if ($aAssign["oxselnid"]) {
                    $aAssign["oxobjectid"] = $oArticle->getId();
                    $oObject2SelectList->assign($aAssign);
                    $oObject2SelectList->oxobject2selectlist__oxsort = new oxField($oDb->getOne("select oxsort from oxselectlist where oxid=" . $oDb->quote($aAssign["oxselnid"])));
                    $oObject2SelectList->save();
                }
            }
        }
        if (!$data->lang && $conf->getShopConfVar('wawiexportcrosssellings'))
            $oDb->execute("delete from oxobject2article where oxarticlenid=" . $oDb->quote($oArticle->getId()));
        if (!$data->lang && $data->crosssellings && $conf->getShopConfVar('wawiexportcrosssellings')) {
            $aAssign = array();
            foreach ($data->crosssellings as $crossselling) {
                $oObject2Article = oxNew("oxbase");
                $oObject2Article->init("oxobject2article");
                $aAssign["oxobjectid"] = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($crossselling->productId));
                if (!$aAssign["oxobjectid"])
                    $aAssign["oxobjectid"] = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($crossselling->foreignId));
                if ($aAssign["oxobjectid"]) {
                    $aAssign["oxarticlenid"] = $oArticle->getId();
                    $oObject2Article->assign($aAssign);
                    $oObject2Article->save();
                }
            }
        }
        if (!$data->lang && $data->accessories && $conf->getShopConfVar('wawiexportaccessories')) {
            if ($oArticle->getId())
                $oDb->execute("delete from oxaccessoire2article where oxarticlenid=" . $oDb->quote($oArticle->getId()));
            $accsort = 0;
            foreach ($data->accessories as $accessory) {
                if (intval($accessory->cartType) == 3) {
                    $accessoryProductId = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($accessory->accessoryId));
                    if (!$accessoryProductId && $accessory->accessoryForeignId)
                        $accessoryProductId = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($accessory->accessoryForeignId));
                    if ($accessoryProductId) {
                        $aAssign = array();
                        $aAssign["oxobjectid"] = $accessoryProductId;
                        $aAssign["oxarticlenid"] = $oArticle->getId();
                        $aAssign["oxsort"] = $accsort++;
                        $oxaccessoire2article = oxNew("oxbase");
                        $oxaccessoire2article->init("oxaccessoire2article");
                        $oxaccessoire2article->assign($aAssign);
                        $oxaccessoire2article->save();
                    }
                }
            }
        }
        foreach ($this->_aProductMultiLangFields as $fields => $val) {
            $farr = explode(",", $fields);
            $oBaseArticle = oxNew("oxarticle");
            $oBaseArticle->setLanguage($iLangId);
            $oBaseArticle->load($oArticle->getId());
            foreach ($farr as $item) {
                $field = "oxarticles__" . $item;
                if (!$oArticle->{$field}->value) {
                    if (!($oArticle->oxarticles__oxparentid->value && $item == "oxtitle")) {
                        $multi_changed = true;
                        $oArticle->{$field} = new oxField($oBaseArticle->{$field}->rawValue);
                    }
                }
            }
        }
        if ($multi_changed) {
            $oArticle->save();
        }
        if (!$oArticle->oxarticles__oxparentid->value && $oArticle->oxarticles__oxid->value) {
            $iVarCount = (int)$oDb->getOne("select count(*) as varcount from oxarticles where oxparentid = " . $oDb->quote($oArticle->oxarticles__oxid->value));
            if ($iVarCount == 0 && $oArticle->oxarticles__oxvarcount->value > 0) {
                $oArticle->oxarticles__oxvarcount = new oxField(0);
                $oArticle->oxarticles__oxvarminprice = new oxField(0);
                $oArticle->oxarticles__oxvarmaxprice = new oxField(0);
                $oArticle->oxarticles__oxvarstock = new oxField(0);
                $oArticle->save();
            }
        } else if ($oArticle->oxarticles__oxparentid->value && $oArticle->oxarticles__oxid->value) {
            $oArticle->oxarticles__oxvarcount = new oxField(0);
            $oArticle->oxarticles__oxvarstock = new oxField(0);
            $oArticle->save();
        }
        if ($oParent) {
            if ($data->createdTimestamp)
                $oDb->execute("update oxarticles set oxinsert=" . $oDb->quote($data->createdTimestamp) . " where oxid=" . $oDb->quote($oArticle->oxarticles__oxid->value));
            else
                $oDb->execute("update oxarticles set oxinsert=" . $oDb->quote($oParent->oxarticles__oxinsert->value) . " where oxid=" . $oDb->quote($oArticle->oxarticles__oxid->value));
        }
        $oArticle->onChange(); //needed for varcount etc.
        $oParent = $oArticle->getParentArticle();
        if ($oArticle->oxarticles__oxparentid->value && $oParent) {
            if (!$oArticle->oxarticles__oxmindeltime->value) {
                $oArticle->oxarticles__oxmindeltime = new oxField($oParent->oxarticles__oxmindeltime->value);
                $oArticle->oxarticles__oxmaxdeltime = new oxField($oParent->oxarticles__oxmaxdeltime->value);
                $oArticle->oxarticles__oxdeltimeunit = new oxField($oParent->oxarticles__oxdeltimeunit->value);
                $oArticle->save();
            }
        }
        if ($conf->getConfigParam('wawiCreateSeoUrlsAsFixed')) {
            $oDb = oxDb::getDb();
            $oDb->execute("update oxseo set oxfixed=1 where oxobjectid=" . $oDb->quote($oArticle->getId()));
        } else {
            $this->updateSEO($oArticle->getId());
        }

        $oArticle->getLink();
        if ($conf->getConfigParam('wawiCreateSeoUrlsAsFixed')) {
            $oDb = oxDb::getDb();
            $oDb->execute("update oxseo set oxfixed=1 where oxobjectid=" . $oDb->quote($oArticle->getId()));
        }
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('wawiUseExtranetMetaDescription') && $data->extranetmetadescription) {
            $iLangId = $this->_getLanguageId($data->lang);
            $this->set_metadescription("product", $oArticle->oxarticles__wwforeignid->value, $oArticle->getId(), $iLangId, $data->extranetmetadescription);
        }
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_ADD_PRODUCT, array($data, $oArticle));
        return $oArticle->getId();
    }

    public function set_product_attributes($data)
    {
        $oDb = oxDb::getDb();
        $id = $data->foreignId;
        $conf = agConfig::getInstance();
        $oArticle = oxNew('oxarticle');
        if ($id)
            $blExists = $oArticle->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oArticle->load($sOxid);
            }
        }
        if ($blExists) {

            $oDb->execute("delete from oxobject2attribute where oxobjectid=" . $oDb->quote($oArticle->getId()));
            if ($data->attributes) {
                foreach ($data->attributes as $attribute) {
                    $oObject2Attribute = oxNew("oxI18n");
                    $attrid = $oDb->getOne("select oxid from oxattribute where wwforeignid=" . $oDb->quote($attribute->attribute_id));
                    if (!$attrid)
                        $attrid = $oDb->getOne("select oxid from oxattribute where oxid=" . $oDb->quote($attribute->foreign_attribute_id));
                    $oObject2Attribute->init("oxobject2attribute");
                    if ($data->lang) {
                        $oObject2Attribute->setLanguage($iLangId);
                        $oObject2Attribute->load($oDb->getOne("select oxid from oxobject2attribute where oxobjectid=" . $oDb->quote($oArticle->getId()) . " and oxattrid=" . $oDb->quote($attrid)));
                    }
                    $oObject2Attribute->oxobject2attribute__oxobjectid = new oxField($oArticle->getId());
                    $oObject2Attribute->oxobject2attribute__oxvalue = new oxField($attribute->value);
                    if ($attrid) {
                        $oObject2Attribute->oxobject2attribute__oxattrid = new oxField($attrid);
                        $oObject2Attribute->save();
                        if (!$conf->getConfigParam('bWAWINotAssignAttributesToCategories')) {
                            $excluded = $conf->getConfigParam('aWAWIExcludedAttributesToCategories');
                            if ($excluded && ($excluded[$attribute->attribute_id] || $excluded[$attrid]))
                                continue;
                            $catIds = $oArticle->getCategoryIds();
                            foreach ($catIds as $catId) {
                                if (!$oDb->getOne("select oxid from oxcategory2attribute where oxobjectid=" . $oDb->quote($catId) . " and oxattrid=" . $oDb->quote($attrid)))
                                    $oDb->execute("insert into oxcategory2attribute (oxid, oxobjectid, oxattrid) values (" .
                                        $oDb->quote(md5($catId . $attrid . "agwawi")) . "," .
                                        $oDb->quote($catId) . "," .
                                        $oDb->quote($attrid) . ")");
                            }
                        }
                    }
                }
            }
        }
    }

    public function set_products_fields($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $prod) {
            $oDb = oxDb::getDb();
            $oArticle = oxNew('oxarticle');
            $oxid = $prod->id;
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($prod->foreignId));
            if (!$sOxid)
                $sOxid = $oxid;
            if ($sOxid && $oArticle->load($sOxid)) {
                foreach ($this->_aProductFields as $sLocalKey => $sRemoteKey) {
                    foreach ($prod->fields as $field) {
                        if ($sRemoteKey == $field->field) {
                            $oxidField = "oxarticles__" . $sLocalKey;
                            $oArticle->{$oxidField} = new oxField($field->value);
                            break;
                        }
                    }
                }
                $oArticle->save();
            }
        }
    }

    public function set_product_field($foreignId, $oxid, $field, $value)
    {
        $oDb = oxDb::getDb();
        $oArticle = oxNew('oxarticle');
        $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($foreignId));
        if (!$sOxid)
            $sOxid = $oxid;
        if ($sOxid && $oArticle->load($sOxid)) {
            foreach ($this->_aProductFields as $sLocalKey => $sRemoteKey) {
                if ($sRemoteKey == $field) {
                    $oxidField = "oxarticles__" . $sLocalKey;
                    $oArticle->{$oxidField} = new oxField($value);
                    $oArticle->save();
                    break;
                }
            }
        }
        return true;
    }

    public function set_customer_field($foreignId, $oxid, $field, $value)
    {
        $oDb = oxDb::getDb();
        $oUser = oxNew('oxuser');
        $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE WWFOREIGNID = ' . $oDb->quote($foreignId));
        if (!$sOxid)
            $sOxid = $oxid;
        if ($sOxid && $oUser->load($sOxid)) {
            foreach ($this->_aCustomerFields as $sLocalKey => $sRemoteKey) {
                if ($sRemoteKey == $field) {
                    $oxidField = "oxuser__" . $sLocalKey;
                    $oUser->{$oxidField} = new oxField($value);
                    $oUser->save();
                    break;
                }
            }
        }
        return true;
    }

    public function set_product_price($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        $oArticle = oxNew('oxarticle');
        if ($id)
            $blExists = $oArticle->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oArticle->load($sOxid);
            }
        }
        if ($blExists) {
            $oArticle->oxarticles__oxprice = new oxField($data->price);
            $iVarCount = (int)$oDb->getOne("select count(*) as varcount from oxarticles where oxparentid = " . $oDb->quote($oArticle->oxarticles__oxid->value));
            if ($iVarCount == 0 && $oArticle->oxarticles__oxvarcount->value > 0) {
                $oArticle->oxarticles__oxvarminprice = new oxField(0);
            }
            $oArticle->onChange();
            $oArticle->save();
            $oDb->execute("delete from oxprice2article where oxartid=" . $oDb->quote($oArticle->getId()));
            if ($data->scaleprices) {
                foreach ($data->scaleprices as $scaleprice) {
                    $oPrice2Article = oxNew("oxbase");
                    $oPrice2Article->init("oxprice2article");
                    $oPrice2Article->oxprice2article__oxartid = new oxField($oArticle->getId());
                    $oPrice2Article->oxprice2article__oxshopid = new oxField($conf->getShopId());
                    $oPrice2Article->oxprice2article__oxamount = new oxField($scaleprice->amountFrom);
                    $oPrice2Article->oxprice2article__oxamountto = new oxField($scaleprice->amountTo);

                    $oGroup = oxNew('oxgroups');
                    if (!$scaleprice->customerGroupForeignId || !$oGroup->load($scaleprice->customerGroupForeignId)) {

                        $sGroupOxid = null;

                        if ($scaleprice->customerGroupId) {
                            $sGroupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups  WHERE WWFOREIGNID = ' . $oDb->quote($scaleprice->customerGroupId));
                            if ($sGroupOxid) {
                                $oGroup->load($sGroupOxid);
                            } else
                                continue;
                        }

                    }
                    $oPrice2Article->oxprice2article__oxgroupsid = new oxField($oGroup->getId());


                    if (intval($scaleprice->priceType) == 0) {
                        $oPrice2Article->oxprice2article__oxaddabs = new oxField($scaleprice->price);
                    } else {
                        $oPrice2Article->oxprice2article__oxaddperc = new oxField($scaleprice->price);
                    }
                    $oPrice2Article->save();
                }
            }
            if (!$conf->getConfigParam('wawiNotExportGroupPrices')) {
                $oDb->execute("delete from oxgroup2price where oxarticleid=" . $oDb->quote($oArticle->getId()));
                if ($data->groupprices) {
                    foreach ($data->groupprices as $groupprice) {
                        //Load group by local id or foreign id if needed
                        $oGroup = oxNew('oxgroups');
                        if (!$groupprice->customerGroupForeignId || !$oGroup->load($groupprice->customerGroupForeignId)) {

                            $sGroupOxid = null;

                            if ($groupprice->customerGroupId) {
                                $sGroupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups  WHERE WWFOREIGNID = ' . $oDb->quote($groupprice->customerGroupId));
                            }

                            if ($sGroupOxid) {
                                $oGroup->load($sGroupOxid);
                            } else {
                                continue;
                            }

                        }

                        $oGroup2Price = oxNew("oxbase");
                        $oGroup2Price->init("oxgroup2price");
                        $oGroup2Price->oxgroup2price__oxarticleid = new oxField($oArticle->getId());
                        $oGroup2Price->oxgroup2price__oxshopid = new oxField($conf->getShopId());
                        $oGroup2Price->oxgroup2price__oxgroupid = new oxField($oGroup->getId());
                        $oGroup2Price->oxgroup2price__oxprice = new oxField($groupprice->price);
                        $oGroup2Price->save();
                    }
                }
            }

        }
    }

    public function set_product_stock($data)
    {
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_PRODUCTSTOCK_UPDATED, array($data));
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $oArticle = oxNew('oxarticle');
        if ($id)
            $blExists = $oArticle->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oArticle->load($sOxid);
            }
        }
        if ($blExists) {
            if ($this->_aOtherSavedProductFields && in_array('oxstock', $this->_aOtherSavedProductFields)) {
                if ($data->active !== NULL) {
                    $oArticle->oxarticles__oxactive = new oxField($data->active);
                    $oArticle->save();
                }
                return;
            }

            if ($data->stock != $oArticle->oxarticles__oxstock->value || ($data->active !== NULL && $oArticle->oxarticles__oxactive->value != $data->active)) {
                $oArticle->oxarticles__oxstock = new oxField($data->stock);
                if ($data->active !== NULL)
                    $oArticle->oxarticles__oxactive = new oxField($data->active);
                $oArticle->oxarticles__oxstockflag = new oxField($data->deliveryStatus);
                $oArticle->oxarticles__oxmindeltime = new oxField($data->minDeliveryTime);
                $oArticle->oxarticles__oxmaxdeltime = new oxField($data->maxDeliveryTime);
                $oArticle->oxarticles__oxdeltimeunit = new oxField($data->deliveryTimeUnit);
                $oArticle->wawiStockUpdate = true;
                $oArticle->save();
                $oArticle->onChange();
            } else {
                $oArticle->oxarticles__oxstockflag = new oxField($data->deliveryStatus);
                $oArticle->oxarticles__oxmindeltime = new oxField($data->minDeliveryTime);
                $oArticle->oxarticles__oxmaxdeltime = new oxField($data->maxDeliveryTime);
                $oArticle->oxarticles__oxdeltimeunit = new oxField($data->deliveryTimeUnit);
                $oArticle->wawiStockUpdate = true;
                $oArticle->save();
            }
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_PRODUCTSTOCK_UPDATED, array($data, $oArticle));
        }
    }

    public function get_attributes($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " where oxattribute.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxattribute', $page);
        } else {
            return $this->getCount('oxattribute');
        }


        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aAttributeFields, null, $this->_aAttributeMultiLangFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxattribute ' . $sLimit;
        //echo $sQ;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $attribute) {
            $oAttribute = oxNew('oxattribute');
            $oAttribute->load($attribute['id']);
            $i18n = $this->get_i18n($oAttribute, "_aAttributeMultiLangFields", "oxattribute");
            if (count($i18n) > 0) {
                $attribute["i18n"] = $i18n;
            }
            $attribute = $this->get_additional_fields('oxattribute', $attribute, $oAttribute);
            $this->resetLanguage($oAttribute);
            $aResult[] = $attribute;
        }
        $this->utf8_encode_deep($aResult);
        return $aResult;
    }

    private function add_attribute($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oAttribute = oxNew('oxattribute');
        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oAttribute->setLanguage($iLangId);
        }
        $blExists = $oAttribute->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxattribute WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oAttribute->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        //Check which parent to use here, wawi is always master
        if ($data->parent_id) {
            $sQ = 'SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->parent_id);
            $sParentId = $oDb->GetOne($sQ);
            $aAssign['oxparentid'] = $sParentId;
        } else {
            $aAssign['oxparentid'] = null;
        }

        foreach ($this->_aAttributeFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oAttribute->assign($aAssign);
        $oAttribute->save();
        return $oAttribute->getId();
    }

    private function remove_attribute($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oAttribute = oxNew('oxattribute');

        $blExists = $oAttribute->load($id);

        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxattribute WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oAttribute->load($sOxid);
            }
        }

        if ($blExists)
            $oAttribute->delete();
    }

    public function get_attribute_values($oProduct)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = array();
        $rows = $oDb->getAll("select * from oxobject2attribute where oxobjectid=" . $oDb->quote($oProduct->getId()));
        foreach ($rows as $row) {
            $value = array();
            $value["attr_id"] = $row["OXATTRID"];
            $value["value"] = $row["OXVALUE"];
            foreach ($this->_getLanguageArray() as $lang) {
                if (!$lang->selected) {
                    $value["i18n"][$lang->abbr]["value"] = $row["OXVALUE_" . $lang->id] ? $row["OXVALUE_" . $lang->id] : $row["OXVALUE"];
                }
            }
            $result[] = $value;
        }
        return $result;
    }

    public function get_option_values($oProduct)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = array();
        $rows = $oDb->getAll("select * from oxobject2selectlist where oxobjectid=" . $oDb->quote($oProduct->getId()));
        foreach ($rows as $row) {
            $value = array();
            $value["option_id"] = $row["OXSELNID"];

            $result[] = $value;
        }
        return $result;
    }

    public function get_category_values($oProduct)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = array();
        $rows = $oDb->getAll("select * from oxobject2category where oxobjectid=" . $oDb->quote($oProduct->getId()));
        foreach ($rows as $row) {
            $value = array();
            $value["category_id"] = $row["OXCATNID"];

            $result[] = $value;
        }
        return $result;
    }

    private function get_variant_deep($variant, $variant_ids)
    {
        $deep = 0;

        while ($variant != null) {
            if ($variant->parent_id)
                $variant = $variant_ids[$variant->parent_id];
            else
                $variant = null;
            $deep++;
        }
        return $deep;
    }

    private function get_variant_name($variant, $product, $variant_ids)
    {
        $variantnames = array();
        while ($variant != null) {
            if ($variant->parent_id) {
                $variant = $variant_ids[$variant->parent_id];
                if ($variant)
                    $variantnames[] = $variant->variantName;
                else
                    $variantnames[] = $product->variantName;
            } else
                $variant = null;

        }
        $variantnames = array_reverse($variantnames);
        return implode(" | ", $variantnames);
    }

    private function get_oxid_variant_active($variant, $product, $variant_ids)
    {
        $arr = array();
        $vid = $variant->id;
        $arr[] = $variant->title;
        $vid = $variant->parent_id;
        $active = $variant->active;
        if (!$active)
            return $active;
        while ($vid != $product->id && $vid != null) {
            $variant = $variant_ids[$vid];
            if (!$variant->active)
                return false;
            $vid = $variant->parent_id;
        }
        return $active;
    }

    private function get_oxid_variant_pipe($variant, $product, $variant_ids)
    {
        $arr = array();
        $vid = $variant->id;
        $arr[] = $variant->title;
        $vid = $variant->parent_id;

        while ($vid != $product->id && $vid != null) {
            $variant = $variant_ids[$vid];
            $arr[] = $variant->title;
            $vid = $variant->parent_id;
        }
        $arr = array_reverse($arr);

        return implode(" | ", $arr);
    }

    private function build_multivariants($end_variants, $product, $oArticle, $variant_ids)
    {
        $max_deep = 0;
        $variantname = null;
        foreach ($end_variants as $variant) {
            $deep = $this->get_variant_deep($variant, $variant_ids);

            if ($deep > $max_deep)
                $max_deep = $deep;
        }
        $deepest_variants = array();
        foreach ($end_variants as $variant) {
            $deep = $this->get_variant_deep($variant, $variant_ids);
            if ($deep == $max_deep) {
                $deepest_variants[] = $variant;
                if (!$variantname)
                    $variantname = $this->get_variant_name($variant, $product, $variant_ids);
            }
        }
        $cnt = count($deepest_variants);
        $conf = agConfig::getInstance();
        $sVersion = agShopVersion::getInstance()->getVersion();
        for ($i = 0; $i < $cnt; $i++) {
            $deepest_variants[$i]->title = $this->get_oxid_variant_pipe($deepest_variants[$i], $product, $variant_ids);
            $deepest_variants[$i]->active = $this->get_oxid_variant_active($deepest_variants[$i], $product, $variant_ids);
            $deepest_variants[$i]->parent_id = $product->id;
            foreach ($this->_aOtherProductFields as $oxidField => $wawiField) {
                if ($deepest_variants[$i]->{$wawiField} === '' || $deepest_variants[$i]->{$wawiField} === NULL) {
                    if (version_compare($sVersion, '4.5.0') >= 0)
                        $deepest_variants[$i]->{$wawiField} = false;
                    else
                        $deepest_variants[$i]->{$wawiField} = '';
                }
            }
            $this->add_product($deepest_variants[$i]);
        }
        return $variantname;
    }

    public function remove_product($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $oArticle = oxNew('oxarticle');

        $blExists = $oArticle->load($id);

        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oArticle->load($sOxid);
            }
        }
        if ($blExists) {
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_REMOVE_PRODUCT, array($oArticle));
            $oArticle->delete();
        }
    }

    public function set_product_pictures($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_product_picture($value);
        }

    }

    public function add_product_picture($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oProduct = oxNew('oxarticle');

        $blExists = $oProduct->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oProduct->load($sOxid);
                if (!$blExists) {
                    $rows = $oDb->getAll('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
                    foreach ($rows as $row) {
                        if ($row['oxid']) {
                            $blExists = $oProduct->load($row['oxid']);
                            if ($blExists)
                                break;
                        }

                    }
                }
            }
        }

        if ($blExists) {

            for ($i = 1; $i <= $this->_iPicsAmount; $i++) {
                $field = "oxarticles__oxpic$i";
                $oProduct->{$field} = new oxField("");
            }
            foreach ($data->pictures as $pic) {
                if ($pic->sort == "thumb")
                    $field = "oxarticles__oxthumb";
                else if ($pic->sort == "icon")
                    $field = "oxarticles__oxicon";
                else
                    $field = "oxarticles__oxpic" . $pic->sort;
                $oProduct->{$field} = new oxField($pic->pictureName);
            }
            //file_put_contents("log.txt",print_r($data->pictures,true));
            $oProduct->save();
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_SET_PRODUCT_PICTURES, array($oProduct, $data));
        }
    }

    public function get_product_options($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " where oxselectlist.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxselectlist', $page);
        } else {
            return $this->getCount('oxselectlist');
        }


        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aOptionFields, null, $this->_aOptionMultiLangFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxselectlist ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $productoption) {
            $oProductOption = oxNew('oxselectlist');
            $oProductOption->load($productoption['id']);
            $i18n = $this->get_i18n($oProductOption, "_aOptionMultiLangFields", "oxselectlist");
            if (count($i18n) > 0) {
                $productoption["i18n"] = $i18n;
            }
            $productoption = $this->get_additional_fields('oxselectlist', $productoption, $oProductOption);
            $this->resetLanguage($oProductOption);
            $aResult[] = $productoption;
        }
        $this->utf8_encode_deep($aResult);
        return $aResult;
    }

    public function set_product_options($data)
    {
        $this->utf8_decode_deep($data);
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_SET_PRODUCT_OPTIONS, array($data));
        foreach ($data as $value) {
            $this->add_product_option($value);
        }
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_SET_PRODUCT_OPTIONS, array($data));
    }

    public function remove_productoptions($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_product_option($value);
        }
    }

    public function add_product_option($data)
    {

        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_ADD_PRODUCT_OPTION, array($data));

        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oSelectList = oxNew('oxselectlist');
        $iLangId = null;
        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oSelectList->setLanguage($iLangId);
        }
        $blExists = $oSelectList->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxselectlist WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oSelectList->load($sOxid);
            }
        }
        //file_put_contents("log.txt",print_r($data,true));
        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;
        $icons = array();
        foreach ($this->_aOptionFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey == "oxvaldesc") {
                $optionvalues = $data->$sRemoteKey;
                $arr = array();
                usort($optionvalues, "cmp_by_sort");
                foreach ($optionvalues as $optionvalue) {
                    $v = $optionvalue->title;
                    if ($optionvalue->surcharge != 0.00) {
                        $v .= '!P!' . $optionvalue->surcharge;
                        if ($optionvalue->surchargeType == "%")
                            $v .= '%';
                    }
                    $icons[] = $optionvalue->icon;
                    $arr[] = $v;
                }
                $aAssign[$sLocalKey] = implode("__@@", $arr) . "__@@";
            } else
                if (isset($data->$sRemoteKey) && $sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                    $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oSelectList->oxselectlist__wwvalicons = new oxField(implode("|", $icons));
        $oSelectList->assign($aAssign);

        $oSelectList->save();

        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_ADD_PRODUCT_OPTION, array($data, $oSelectList));

        return $oSelectList->getId();
    }

    public function remove_product_option($data)
    {

        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oSelectList = oxNew('oxselectlist');

        $blExists = $oSelectList->load($id);

        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxselectlist WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oSelectList->load($sOxid);
            }
        }
        if ($blExists)
            $oSelectList->delete();
    }

    public function get_object2options($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2selectlist', $page);
        } else {
            return $this->getCount('oxobject2selectlist');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aObject2OptionFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxobject2selectlist ORDER BY oxsort ASC ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function set_object2options($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        foreach ($data as $value) {
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($value->product_id));
            if ($prodid)
                $oDb->execute("delete from oxobject2selectlist where oxobjectid=" . $oDb->quote($prodid));
        }
        foreach ($data as $value) {
            $this->add_object2options($value);
        }
    }

    public function add_object2options($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->options as $option) {
            $oObject2SelectList = oxNew("oxbase");
            $oObject2SelectList->init("oxobject2selectlist");
            $aAssign["oxselnid"] = $oDb->getOne("select oxid from oxselectlist where wwforeignid=" . $oDb->quote($option->id));
            $aAssign["oxobjectid"] = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($data->product_id));
            $oObject2SelectList->assign($aAssign);
            $oObject2SelectList->save();
        }
        return true;
    }

    public function get_categories($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " where oxcategories.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = ' ORDER BY oxleft ASC ' . $this->getLimit('oxcategories', $page);
        } else {
            return $this->getCount('oxcategories');
        }

        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aCategoryFields, null, $this->_aCategoryMultiLangFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxcategories ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $category) {
            $oCategory = oxNew('oxcategory');
            $oCategory->load($category['id']);
            $i18n = $this->get_i18n($oCategory, "_aCategoryMultiLangFields", "oxcategories");
            if (count($i18n) > 0) {
                $category["i18n"] = $i18n;
            }
            $category = $this->get_additional_fields('oxcategory', $category, $oCategory);
            $this->resetLanguage($oCategory);
            $aResult[] = $category;
        }
        $this->utf8_encode_deep($aResult);
        return $aResult;
    }

    public function set_categories($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_category($value);
        }
        $this->_clearSeoCache();
        if (count($data) > 0 && !$data[0]->lang) {
            $oCatList = oxNew("oxCategoryList");
            $oCatList->updateCategoryTree(false);
        }
        $this->_clearCatCache();
    }

    public function remove_categories($data)
    {
        $this->utf8_decode_deep($data);

        foreach ($data as $value) {
            $this->remove_category($value);
        }
        $this->_clearSeoCache();
        $oCatList = oxNew("oxCategoryList");
        $oCatList->updateCategoryTree(false);
        $this->_clearCatCache();
    }

    public function add_category($data)
    {
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_ADD_CATEGORY, array($data));
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oCategory = oxNew('oxcategory');
        //file_put_contents("log.txt",print_r($data,true));
        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oCategory->setLanguage($iLangId);
        }
        $blExists = $oCategory->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxcategories WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oCategory->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;
        $aAssign['wwforeignparentid'] = $data->parent_id;
        //Check which parent to use here, wawi is always master
        if ($data->parent_id) {
            $sQ = 'SELECT oxid FROM oxcategories WHERE WWFOREIGNID = ' . $oDb->quote($data->parent_id);

            $sParentId = $oDb->GetOne($sQ);
            if (!$sParentId) {
                $sQ = 'SELECT oxid FROM oxcategories WHERE oxid = ' . $oDb->quote($data->foreignParentId);
                $sParentId = $oDb->GetOne($sQ);
                if (!$sParentId)
                    return;
            }
            $aAssign['oxparentid'] = $sParentId;
        } else {
            $aAssign['oxparentid'] = 'oxrootid';
        }

        foreach ($this->_aCategoryFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid' && $sLocalKey != 'wwforeignparentid' && $sLocalKey != "oxparentid")
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oCategory->assign($aAssign);
        //file_put_contents(getShopBasePath()."wawi/log.txt",print_r( $aAssign,true)."\n",FILE_APPEND);
        if (!$blExists) {
            //Set defaults for new entries
            $oCategory->oxcategories__oxhidden = new oxField(0);
            $oCategory->oxcategories__oxdefsortmode = new oxField(0);
            $oCategory->oxcategories__oxvat = new oxField(null);
            $oCategory->oxcategories__oxpricefrom = new oxField(0);
            $oCategory->oxcategories__oxpriceto = new oxField(0);
            $oCategory->setId(null);
        }
        $oCategory->save();
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('wawiCreateSeoUrlsAsFixed')) {
            $oCategory->getLink();
            $oDb = oxDb::getDb();
            $oDb->execute("update oxseo set oxfixed=1 where oxobjectid=" . $oDb->quote($oCategory->getId()));
        } else {
            $this->updateSEO($oCategory->getId());
        }
        $oDb->execute("delete from oxcategory2group where oxobjectid=" . $oDb->quote($oCategory->getId()));
        foreach ($data->groups as $group) {
            $oArticle2Group = oxNew("oxbase");
            $oArticle2Group->init("oxcategory2group");
            $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($group->id));
            if (!$aAssign["oxgroupsid"])
                $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($group->foreignId));
            $aAssign["oxobjectid"] = $oCategory->getId();
            $oArticle2Group->assign($aAssign);
            $oArticle2Group->save();
        }
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('wawiUseExtranetMetaDescription') && $data->extranetmetadescription) {
            $iLangId = $this->_getLanguageId($data->lang);
            $this->set_metadescription("category", $oCategory->oxcategories__wwforeignid->value, $oCategory->getId(), $iLangId, $data->extranetmetadescription);
        }
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_ADD_CATEGORY, array($data, $oCategory));
        return $oCategory->getId();
    }

    public function remove_non_existing_categories($data)
    {
        $quotedIds = array();
        $oDb = oxDb::getDb();

        foreach ($data as $id) {
            array_push($quotedIds, $oDb->quote($id));
        }

        $sQ = 'SELECT oxid FROM oxcategories WHERE wwforeignid NOT IN (' . implode(",", $quotedIds) . ')';
        $rs = $oDb->Execute($sQ);

        $oCategory = oxNew('oxcategory');
        $aIds = array();

        while (!$rs->EOF) {
            array_push($aIds, $rs->fields[0]);
            $oCategory->delete($rs->fields[0]);
            $rs->MoveNext();
        }

        return $aIds;

    }

    public function remove_category($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $oCategory = oxNew('oxcategory');
        $blExists = $oCategory->load($id);
        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxcategories WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oCategory->load($sOxid);
            }
        }
        if ($blExists) {
            if (!$oDb->getOne("select count(*) from oxcategories where oxparentid=" . $oDb->quote($oCategory->getId()))) {
                $oDb->execute("update oxcategories set oxleft=0, oxright=1 where oxid=" . $oDb->quote($oCategory->getId()));
                $oCategory->load($oCategory->getId());
            }
            $oDb = oxDb::getDb();
            $subCats = $oDb->getAll("select OXID, WWFOREIGNID from oxcategories where oxparentid=" . $oDb->quote($oCategory->getId()));
            foreach ($subCats as $row) {
                $obj = new stdClass();
                $obj->id = $row[1];
                $obj->foreignId = $row[0];
                $this->remove_category($obj);
            }
            $oCatList = oxNew("oxCategoryList");
            $oCatList->updateCategoryTree(false);
            $this->_clearCatCache();
            $id = $oCategory->getId();
            $oCategory = oxNew("oxcategory");
            $oCategory->load($id);
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_REMOVE_CATEGORY, array($oCategory));
            $oCategory->delete();
        }
        //file_put_contents(getShopBasePath()."wawi/log.txt",print_r($data,true),FILE_APPEND);
    }

    public function get_object2categories($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2category', $page);
        } else {
            return $this->getCount('oxobject2category');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aObject2CategoryFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxobject2category ORDER BY oxpos ASC ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        $newresult = array();
        foreach ($result as $item) {
            $item["main"] = $oDb->getOne("select oxobjectid from oxobject2category where oxcatnid=" . $oDb->quote($item["category_id"]) . " and oxobjectid=" . $oDb->quote($item["product_id"]) . " and oxtime=0") ? true : false;
            $newresult[] = $item;
        }
        $result = $newresult;
        return $result;
    }

    public function set_object2categories($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        $sortarr = array();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        $oConf = agConfig::getInstance();
        if ($oConf->getShopConfVar('wawiexportparentcategories'))
            foreach ($data as $value) {
                $cats = array();
                foreach ($value->categories as $category) {
                    $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($category->id));
                    if (!$catid)
                        $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($category->foreignId));
                    if (!$catid)
                        break;
                    $cats[$catid] = $catid;
                }
                foreach ($cats as $catid) {
                    $parentId = $catid;
                    while (true) {
                        $parentId = $oDb->getOne("select oxparentid from oxcategories where oxid=" . $oDb->quote($parentId));
                        if (!$parentId)
                            break;
                        if (!$cats[$parentId]) {
                            $obj = new stdClass();
                            $obj->foreignId = $parentId;
                            $obj->id = "-";
                            $value->categories[] = $obj;
                            $cats[$parentId] = $parentId;
                        }
                    }
                }
            }

        foreach ($data as $value) {
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($value->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($value->foreignProductId));
            if ($prodid) {
                $rows = $oDb->getAll("select oxcatnid, oxpos from oxobject2category where oxobjectid=" . $oDb->quote($prodid));
                foreach ($rows as $row) {
                    $sortarr[$prodid][$row[0]] = $row[1];
                }
                $oDb->execute("delete from oxobject2category where oxobjectid=" . $oDb->quote($prodid));

            }
        }
        foreach ($data as $value) {
            $this->add_object2categories($value);
        }
        foreach ($sortarr as $prodid => $catarr) {
            foreach ($catarr as $catid => $pos) {
                $oDb->execute("update oxobject2category set oxpos=" . intval($pos) . " where oxobjectid=" . $oDb->quote($prodid) . " and oxcatnid=" . $oDb->quote($catid));
            }
        }
    }

    public function add_object2categories($data)
    {

        $oDb = oxDb::getDb();
        $oConf = agConfig::getInstance();
        $aAssign = array();
        foreach ($data->categories as $category) {
            $oObject2Category = oxNew("oxbase");
            $oObject2Category->init("oxobject2category");
            $aAssign["oxcatnid"] = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($category->id));
            if (!$aAssign["oxcatnid"])
                $aAssign["oxcatnid"] = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($category->foreignId));
            if (!$aAssign["oxcatnid"])
                continue;
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($data->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($data->foreignProductId));
            if (!$prodid)
                continue;
            $aAssign["oxobjectid"] = $prodid;
            if ($data->mainCategoryId && ($category->id == $data->mainCategoryId || $data->mainCategoryForeignId == $category->foreignId && $data->mainCategoryForeignId && $category->foreignId)) {
                $aAssign["oxtime"] = 0;
            } else {
                $aAssign["oxtime"] = 10;
            }
            //$aAssign["oxpos"] = max($oDb->getOne("select oxsort from oxcategories where oxid=".$oDb->quote($aAssign["oxcatnid"])),0);
            $oObject2Category->assign($aAssign);
            $oObject2Category->save();
            if ($aAssign["oxcatnid"])
                agUtilsCount::getInstance()->resetCatArticleCount($aAssign["oxcatnid"]);

        }
        return true;
    }

    public function set_category2objects($data)
    {
        ini_set('memory_limit', '1500M');
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        $oCategory = null;
        $objids = array();
        $parentid = null;
        $notdelete = false;
        //$oDb->execute("delete from oxobject2category where oxcatnid=");
        foreach ($data as $value) {
            if (!$oCategory) {
                if ($value->categoryId == "-")
                    $notdelete = true;
                $catOxid = $oDb->GetOne('SELECT oxid FROM oxcategories WHERE oxid = ' . $oDb->quote($value->foreignCategoryId));
                if (!$catOxid)
                    $catOxid = $oDb->GetOne('SELECT oxid FROM oxcategories WHERE wwforeignid = ' . $oDb->quote($value->categoryId));
                if ($catOxid) {
                    $oCategory = oxNew("oxcategory");
                    $oCategory->load($catOxid);
                } else
                    return;
            }
            if (!$value->id)
                break;
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE oxid = ' . $oDb->quote($value->foreignId));
            if (!$sOxid)
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE wwforeignid = ' . $oDb->quote($value->id));
            if ($sOxid) {
                $cat2objid = $oDb->getOne("select oxid from oxobject2category where oxobjectid=" . $oDb->quote($sOxid) . " and oxcatnid=" . $oDb->quote($oCategory->getId()));
                if (!$cat2objid) {
                    $cat2objid = md5($sOxid . $oCategory->getId() . "agwawi");
                    $oDb->execute("insert into oxobject2category (oxid, oxobjectid, oxpos, oxcatnid, oxtime) values (" .
                        $oDb->quote($cat2objid) . "," .
                        $oDb->quote($sOxid) . "," .
                        $oDb->getOne('SELECT oxsort FROM oxarticles WHERE oxid=' . $oDb->quote($sOxid)) . "," .
                        $oDb->quote($oCategory->getId()) . "," .
                        ($value->main ? 0 : 10) . ")");
                }
                $objids[] = $oDb->quote($cat2objid);
            }
            $parentid = $oCategory->oxcategories__oxparentid->value;
        }
        if (!$notdelete) {
            if (count($objids) == 0)
                $oDb->execute("delete from oxobject2category where oxcatnid=" . $oDb->quote($oCategory->getId()));
            else
                $oDb->execute("delete from oxobject2category where oxcatnid=" . $oDb->quote($oCategory->getId()) . " and not (oxid in (" . implode(",", $objids) . ")) ");
        }
        agUtilsCount::getInstance()->resetCatArticleCount($oCategory->getId());
        $oConf = agConfig::getInstance();
        if ($parentid && $oConf->getShopConfVar('wawiexportparentcategories')) {
            $newdata = array();
            foreach ($data as $value) {
                $obj = clone $value;
                $obj->foreignCategoryId = $parentid;
                $obj->categoryId = '-';
                $newdata[] = $obj;
            }
            $this->set_category2objects($newdata);
        }
    }

    public function set_productoption2products($data)
    {
        ini_set('memory_limit', '1500M');
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        $oOption = null;
        $objids = array();
        //$oDb->execute("delete from oxobject2category where oxcatnid=");
        foreach ($data as $value) {
            if (!$oOption) {
                $optionOxid = $oDb->GetOne('SELECT oxid FROM oxselectlist WHERE oxid = ' . $oDb->quote($value->foreignOptionId));
                if (!$optionOxid)
                    $optionOxid = $oDb->GetOne('SELECT oxid FROM oxselectlist WHERE wwforeignid = ' . $oDb->quote($value->optionId));
                if ($optionOxid) {
                    $oOption = oxNew("oxselectlist");
                    $oOption->load($optionOxid);
                } else
                    return;
            }
            if (!$value->id)
                break;
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE oxid = ' . $oDb->quote($value->foreignId));
            if (!$sOxid)
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE wwforeignid = ' . $oDb->quote($value->id));
            if ($sOxid) {
                $option2objid = $oDb->getOne("select oxid from oxobject2selectlist where oxobjectid=" . $oDb->quote($sOxid) . " and oxselnid=" . $oDb->quote($oOption->getId()));
                if (!$option2objid) {
                    $option2objid = md5($sOxid . $oOption->getId() . "agwawi");
                    $oDb->execute("insert into oxobject2selectlist (oxid, oxobjectid, oxsort, oxselnid) values (" .
                        $oDb->quote($option2objid) . "," .
                        $oDb->quote($sOxid) . "," .
                        $oDb->quote($oOption->oxselectlist__oxsort->value) . "," .
                        $oDb->quote($oOption->getId()) . ")");
                }
                $objids[] = $oDb->quote($option2objid);
            }

        }
        if (count($objids) == 0)
            $oDb->execute("delete from oxobject2selectlist where oxselnid=" . $oDb->quote($oOption->getId()));
        else
            $oDb->execute("delete from oxobject2selectlist where oxselnid=" . $oDb->quote($oOption->getId()) . " and not (oxid in (" . implode(",", $objids) . ")) ");
    }

    public function set_customergroup2customers($data)
    {
        ini_set('memory_limit', '1500M');
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        $oGroup = null;
        $objids = array();
        //$oDb->execute("delete from oxobject2category where oxcatnid=");

        foreach ($data as $value) {
            if (!$oGroup) {
                $groupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups WHERE oxid = ' . $oDb->quote($value->foreignGroupId));
                if (!$groupOxid)
                    $groupOxid = $oDb->GetOne('SELECT oxid FROM oxgroups WHERE wwforeignid = ' . $oDb->quote($value->groupId));

                if ($groupOxid) {
                    $oGroup = oxNew("oxgroups");
                    $oGroup->load($groupOxid);
                } else
                    return;
            }
            if (!$value->id)
                break;
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE oxid = ' . $oDb->quote($value->foreignId));
            if (!$sOxid)
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE wwforeignid = ' . $oDb->quote($value->id));
            if ($sOxid) {

                $group2objid = $oDb->getOne("select oxid from oxobject2group where oxobjectid=" . $oDb->quote($sOxid) . " and oxgroupsid=" . $oDb->quote($oGroup->getId()));
                if (!$group2objid) {
                    $group2objid = md5($sOxid . $oGroup->getId() . "agwawi");
                    $oDb->execute("insert into oxobject2group (oxid, oxobjectid, oxshopid, oxgroupsid) values (" .
                        $oDb->quote($group2objid) . "," .
                        $oDb->quote($sOxid) . "," .
                        $oDb->quote($conf->getShopId()) . "," .
                        $oDb->quote($oGroup->getId()) . ")");
                }
                $objids[] = $oDb->quote($group2objid);
            }

        }
    }

    public function get_customer2groups($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2group', $page);
        } else {
            return $this->getCount('oxobject2group');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aCustomer2GroupFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxobject2group ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function set_customer2groups($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {

            $userid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($value->customer_id));

            if (!$userid)
                $userid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($value->foreignCustomerId));

            if ($userid)
                $oDb->execute("delete from oxobject2group where oxobjectid=" . $oDb->quote($userid));

            $this->add_customer2group($value);
        }
    }

    public function add_customer2group($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();

        foreach ($data->groups as $group) {
            $oObject2Group = oxNew("oxbase");
            $oObject2Group->init("oxobject2group");
            $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($group->id));
            if (!$aAssign["oxgroupsid"])
                $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($group->foreignId));
            $custid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($data->customer_id));
            if (!$custid)
                $custid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($data->foreignCustomerId));
            if (!$custid)
                continue;
            $aAssign["oxobjectid"] = $custid;
            $oObject2Group->assign($aAssign);
            $oObject2Group->save();
        }
        return true;
    }


    public function get_product2groups($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxarticle2group', $page);
        } else {
            return $this->getCount('oxarticle2group');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aProduct2GroupFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxarticle2group ' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function set_product2groups($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {

            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($value->product_id));

            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($value->foreignProductId));

            if ($prodid)
                $oDb->execute("delete from oxarticle2group where oxobjectid=" . $oDb->quote($prodid));

            $this->add_product2group($value);
        }
    }

    public function add_product2group($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();

        foreach ($data->groups as $group) {
            $oArticle2Group = oxNew("oxbase");
            $oArticle2Group->init("oxarticle2group");
            $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($group->id));
            if (!$aAssign["oxgroupsid"])
                $aAssign["oxgroupsid"] = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($group->foreignId));
            if (!$aAssign["oxgroupsid"] && agConfig::getInstance()->getConfigParam('wawiNotExportProductGroupsIfGroupNotExists'))
                continue;
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($data->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($data->foreignProductId));
            $aAssign["oxobjectid"] = $prodid;
            $oArticle2Group->assign($aAssign);
            $oArticle2Group->save();
        }
        return true;
    }

    public function get_customer_by_token($token)
    {
        $oDb = oxDb::getDb();
        $userid = $oDb->getOne("select userid from wawitokens where id=" . $oDb->quote($token) . " and expired > now()");
        if ($userid) {
            $oUser = oxNew("oxuser");
            if ($oUser->load($userid)) {
                return array("id" => $oUser->getId(), "foreignId" => $oUser->oxuser__wwforeignid->value);
            }
        }
    }

    public function is_registered_customer($id, $foreignId)
    {
        $oUser = oxNew("oxuser");
        if ($oUser->load($id)) {
            if ($oUser->oxuser__oxpassword->value)
                return "YES";
            else
                return "NO";
        } else {
            $oDb = oxDb::getDb();
            $id = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($foreignId));
            if (!$id)
                return "NO";
            $oUser->load($id);
            if ($oUser->oxuser__oxpassword->value)
                return "YES";
            else
                return "NO";
        }
    }

    public function is_valid_customer_password($id, $foreignId, $pass)
    {
        $oUser = oxNew("oxuser");
        if ($oUser->load($id)) {
            $oNewUser = oxNew("oxuser");
            if ($oNewUser->login($oUser->oxuser__oxusername->value, $pass))
                return "YES";
            else
                return "NO";
        } else {
            $oDb = oxDb::getDb();
            $id = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($foreignId));
            if (!$id)
                return "NO";
            $oUser->load($id);
            $oNewUser = oxNew("oxuser");
            if ($oNewUser->login($oUser->oxuser__oxusername->value, $pass))
                return "YES";
            else
                return "NO";
        }
    }

    public function get_customer_newsletter_subscription_date($id, $foreignId)
    {
        $oUser = oxNew("oxuser");
        if (!$oUser->load($id)) {
            $oDb = oxDb::getDb();
            $id = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($foreignId));
            if (!$id)
                return null;
            $oUser->load($id);
        }
        $oSubscription = $oUser->getNewsSubscription();
        if ($oSubscription && $oSubscription->oxnewssubscribed__oxsubscribed->value && $oSubscription->oxnewssubscribed__oxunsubscribed->value == "0000-00-00 00:00:00"
            && intval($oSubscription->oxnewssubscribed__oxdboptin->value) === 1)
            return $oSubscription->oxnewssubscribed__oxsubscribed->value;
    }

    public function get_customers($page = false, $fromDate = null, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = ' where oxid=' . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxuser', $page, $fromDate);
        } else {
            return $this->getCount('oxuser', $fromDate);
        }

        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aCustomerFields);
        $sWhere = '';
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('warexoUseShopId'))
            $sWhere = ' where oxuser.oxshopid = ' . $oDb->quote($conf->getShopId());
        if ($sWhere)
            $sQ = 'SELECT ' . $sFields . ' FROM oxuser ' . $sWhere . ' ' . str_replace('where', 'and', $sLimit);
        else
            $sQ = 'SELECT ' . $sFields . ' FROM oxuser ' . $sWhere . ' ' . $sLimit;
        //echo $sQ;
        $result = $this->getRecords($oDb, $sQ);
        $newresult = array();
        foreach ($result as $data) {
            $oUser = oxNew("oxuser");
            $oUser->load($data["id"]);
            $data["addresses"] = $this->get_addresses($data["id"]);
            $data["country"] = $oDb->getOne("select oxisoalpha3 from oxcountry where oxid=" . $oDb->quote($data["country"]));
            $data["state"] = $oDb->getOne("select oxisoalpha2 from oxstates where oxid=" . $oDb->quote($data["state"]));
            $data["groups"] = $oDb->getAll("select g.oxid as id, g.wwforeignid as foreignId from oxobject2group o2g LEFT JOIN oxgroups g ON o2g.oxgroupsid = g.oxid where g.oxid IS NOT NULL AND o2g.oxobjectid=" . $oDb->quote($data["id"]));
            $data["username"] = $data["email"];
            $data["blocked"] = !$oUser->oxuser__oxactive->value;
            $data["created"] = $oUser->oxuser__oxcreate->value;
            $data = $this->get_additional_fields('oxuser', $data, $oUser);
            $newresult[] = $data;
        }
        $result = $newresult;
        return $result;
    }

    public function get_customergroups($page = false)
    {

        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxgroups', $page);
        } else {
            return $this->getCount('oxgroups');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aCustomerGroupFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxgroups ' . $sLimit;
        //echo $sQ;
        $result = $this->getRecords($oDb, $sQ);

        return $result;
    }

    public function get_addresses($userid)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        //$sQ = 'SELECT oxid as id,  oxfname as firstName, oxlname as lastName, CONCAT_WS(" ", oxstreet, oxstreetnr) as street, oxcity as city, oxzip as zip, oxfax as fax, oxsal as salutation, oxcountryid as country, oxstateid as state, ox FROM oxaddress where oxuserid='.$oDb->quote($userid);
        $sFields = $this->toRemoteSqlKeys($this->_aAddressFields);
        $sQ = 'SELECT ' . $sFields . ' FROM oxaddress where oxuserid=' . $oDb->quote($userid);
        $result = $this->getRecords($oDb, $sQ);
        $newresult = array();
        foreach ($result as $data) {
            $data["country"] = $oDb->getOne("select oxisoalpha3 from oxcountry where oxid=" . $oDb->quote($data["country"]));
            $data["state"] = $oDb->getOne("select oxisoalpha2 from oxstates where oxid=" . $oDb->quote($data["state"]));
            $newresult[] = $data;
        }
        $result = $newresult;
        return $result;
    }

    public function set_customers($data)
    {
        $this->utf8_decode_deep($data);
        $messages = array();
        foreach ($data as $value) {
            try {
                $this->add_customer($value);
            } catch (Exception $ex) {
                $messages[] = $ex->getMessage();
            }
        }
        if (count($messages) > 0)
            return implode("\n", $messages);
    }

    public function get_street_parts($street)
    {
        $aStreet = preg_split('~([^\d]*) (.*)~', $street, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $sNumber = array_pop($aStreet);
        if (count($aStreet) == 0)
            $sNumber = ".";
        else if (strlen($sNumber) > 5 && strpos($sNumber, "-") === FALSE)
            $sNumber = ".";
        else
            $street = implode(' ', $aStreet);
        return array(
            'name' => $street,
            'number' => $sNumber
        );
    }

    public function set_customer_addresses($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oUser = oxNew('oxuser');
        $blExists = $oUser->load($id);
        $conf = agConfig::getInstance();
        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oUser->load($sOxid);
            }
        }
        if (!$blExists)
            return;

        $oDb->execute("delete from oxaddress where oxuserid=" . $oDb->quote($oUser->getId()));
        //file_put_contents('log.txt',print_r($data, true)."ok", FILE_APPEND);
        foreach ($data->addresses as $address) {
            $aAssign = array();
            $aAssign['wwforeignid'] = $address->id;
            foreach ($this->_aAddressFields as $sLocalKey => $sRemoteKey) {
                if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid') {
                    $aAssign[$sLocalKey] = $address->$sRemoteKey;
                }
            }
            $oAddress = oxNew("oxaddress");
            $oAddress->assign($aAssign);
            $oAddress->oxaddress__oxuserid = new oxField($oUser->getId());
            $streetData = $this->get_street_parts($address->street);
            $oAddress->oxaddress__oxstreet = new oxField($streetData['name']);
            $oAddress->oxaddress__oxstreetnr = new oxField($streetData['number']);
            if ($address->country)
                $oAddress->oxaddress__oxcountryid = new oxField($oDb->getOne("select oxid from oxcountry where oxisoalpha2=" . $oDb->quote($address->country)));
            if ($address->state)
                $oAddress->oxaddress__oxstateid = new oxField($oDb->getOne("select oxid from oxstates where oxisoalpha2=" . $oDb->quote($address->state)));
            $oAddress->save();
        }

    }

    public function add_customer($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oUser = oxNew('oxuser');
        $blExists = $oUser->load($id);
        $conf = agConfig::getInstance();
        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oUser->load($sOxid);
            }
        }
        if (!$blExists && !$data->email)
            return;
        if (!$blExists && $data->email) {
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE OXUSERNAME = ' . $oDb->quote($data->email));
            if ($sOxid)
                return;
        }
        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        foreach ($this->_aCustomerFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid') {
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
            }
        }
        //file_put_contents('customers.log',print_r($data, true)."\n", FILE_APPEND);
        $oUser->assign($aAssign);
        $streetData = $this->get_street_parts($data->street);
        $oUser->oxuser__oxstreet = new oxField($streetData['name']);
        $oUser->oxuser__oxstreetnr = new oxField($streetData['number']);
        $oUser->oxuser__wwcustomernumber = new oxField($data->customerNumber);
        if ($data->country)
            $oUser->oxuser__oxcountryid = new oxField($oDb->getOne("select oxid from oxcountry where oxisoalpha2=" . $oDb->quote($data->country)));
        if ($data->state)
            $oUser->oxuser__oxstateid = new oxField($oDb->getOne("select oxid from oxstates where oxisoalpha2=" . $oDb->quote($data->state)));

        if ($data->blocked)
            $oUser->oxuser__oxactive = new oxField(0);
        else
            $oUser->oxuser__oxactive = new oxField(1);
        if (!$blExists)
            $oUser->setPassword(uniqid());
        $oUser->save();

        //Assign groups
        if ($data->groups) {

            $oDb->execute("delete from oxobject2group where LEFT(oxgroupsid,2) != 'ox' AND oxobjectid = " . $oDb->quote($oUser->getId()));

            foreach ($data->groups as $group) {

                $oGroup = oxNew('oxgroups');

                if (!$group->foreignId || !$oGroup->load($group->foreignId)) {
                    $sOxid = $oDb->GetOne('SELECT oxid FROM oxgroups WHERE WWFOREIGNID = ' . $oDb->quote($group->id));
                    if (!$oGroup->load($sOxid)) {
                        continue;
                    }
                }

                //Check if user already in oxid default group
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxobject2group WHERE oxshopid = ' . $oDb->quote($conf->getShopId()) . ' AND oxobjectid = ' . $oDb->quote($oUser->getId()) . ' AND oxgroupsid = ' . $oDb->quote($oGroup->getId()));

                if (!$sOxid) {
                    $o2g = oxNew('oxbase');
                    $o2g->init('oxobject2group');
                    $o2g->oxobject2group__oxshopid = new oxField($conf->getShopId());
                    $o2g->oxobject2group__oxobjectid = new oxField($oUser->getId());
                    $o2g->oxobject2group__oxgroupsid = new oxField($oGroup->getId());
                    $o2g->save();
                }


            }
        }
        if ($data->bicCode && $data->ibanNumber) {
            $sPaymentId = $oDb->getOne("select oxid from oxuserpayments where oxuserid=" . $oDb->quote($oUser->getId()) . " and oxpaymentsid=" . $oDb->quote('oxiddebitnote') . " order by oxtimestamp desc");
            $oUserPayment = oxNew("oxuserpayment");
            if ($sPaymentId)
                $oUserPayment->load($sPaymentId);
            $oUserPayment->oxuserpayments__oxpaymentsid = new oxField('oxiddebitnote');
            $oUserPayment->oxuserpayments__oxuserid = new oxField($oUser->getId());
            $aDynvalues = array();
            $aDynvalues['lsktonr'] = $data->ibanNumber;
            $aDynvalues['lsblz'] = $data->bicCode;
            $aDynvalues['lsbankname'] = $data->bankName;
            $aDynvalues['lsktoinhaber'] = $data->bankAccountHolder ? $data->bankAccountHolder : ($oUser->oxuser__oxcompany->value ? $oUser->oxuser__oxcompany->value : $oUser->oxuser__oxfname->value . " " . $oUser->oxuser__oxlname->value);
            $oUserPayment->oxuserpayments__oxvalue = new oxField(agUtils::getInstance()->assignValuesToText($aDynvalues));
            $oUserPayment->save();
        }
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_ADD_CUSTOMER, array($data, $oUser));
        return $oUser->getId();
    }

    public function remove_customer($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oUser = oxNew('oxuser');

        $blExists = $oUser->load($id);
        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxuser WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oUser->load($sOxid);
            }
        }
        if ($blExists) {
            $oUser->delete();
        }
    }

    public function remove_customers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_customer($value);
        }
    }


    public function set_customergroups($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_customergroup($value);
        }
    }

    public function add_customergroup($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oGroup = oxNew('oxgroups');
        $blExists = $oGroup->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxgroups WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oGroup->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        foreach ($this->_aCustomerGroupFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid') {
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
            }
        }

        $oGroup->assign($aAssign);
        $oGroup->save();

        return $oGroup->getId();
    }

    public function remove_customergroup($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oGroup = oxNew('oxgroups');

        $blExists = $oGroup->load($id);
        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxgroups WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oGroup->load($sOxid);
            }
        }
        if ($blExists) {
            $oGroup->delete();
        }
    }

    public function remove_customergroups($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_customergroup($value);
        }
    }


    public function get_countries($page = false)
    {

        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxcountry', $page);
        } else {
            return $this->getCount('oxcountry');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sQ = 'SELECT oxid as id, oxactive as active, oxtitle as title, oxisoalpha3 as isoCode, oxisoalpha2 as isoCode2, oxorder as sort FROM oxcountry' . $sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $country) {
            $oCountry = oxNew('oxcountry');
            $oCountry->load($country['id']);
            $i18n = $this->get_i18n($oCountry, "_aCountryMultiLangFields", "oxcountry");
            if (count($i18n) > 0) {
                $country["i18n"] = $i18n;
            }
            $this->resetLanguage($oCountry);
            $aResult[] = $country;
        }

        $this->utf8_encode_deep($aResult);
        return $aResult;
    }

    public function add_country($data)
    {
    }

    public function remove_country($data)
    {
    }

    public function get_states($page = false)
    {

        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxstates', $page);
        } else {
            return $this->getCount('oxstates');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sQ = 'SELECT oxid as id, 1 as active, 0 as sort, oxcountryid as country, oxtitle as title, oxisoalpha2 as isoCode FROM oxstates' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function add_state($data)
    {
    }

    public function remove_state($data)
    {
    }

    public function set_vendors($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_vendor($value);
        }
    }

    public function remove_vendors($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_vendor($value);
        }
    }

    public function get_vendors($page = false)
    {

        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxvendor', $page);
        } else {
            return $this->getCount('oxvendor');
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sQ = 'SELECT oxid as id, oxtitle as title, oxactive as active, oxshortdesc as description, oxicon as icon FROM oxvendor' . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function add_vendor($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oVendor = oxNew('oxvendor');

        $blExists = $oVendor->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvendor WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oVendor->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        foreach ($this->_aVendorFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oVendor->assign($aAssign);

        $oVendor->save();

        return $oVendor->getId();
    }

    public function remove_vendor($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oVendor = oxNew('oxvendor');

        $blExists = $oVendor->load($id);
        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvendor WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oVendor->load($sOxid);
            }
        }
        if ($blExists) {
            $oVendor->delete();
        }
    }

    public function set_manufacturers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_manufacturer($value);
        }

    }

    public function remove_manufacturers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_manufacturer($value);
        }

    }

    public function get_manufacturers($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " where oxmanufacturers.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxmanufacturers', $page);
        } else {
            return $this->getCount('oxmanufacturers');
        }


        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aManufacturerFields, null, $this->_aManufacturerMultiLangFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxmanufacturers ' . $sLimit;
        //$sQ = 'SELECT oxid as id, oxtitle as title, oxactive as active, oxshortdesc as description, oxicon as icon FROM oxmanufacturers'.$sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $manufacturer) {
            $oManufacturer = oxNew('oxmanufacturer');
            $oManufacturer->load($manufacturer['id']);
            $i18n = $this->get_i18n($oManufacturer, "_aManufacturerMultiLangFields", "oxmanufacturers");
            if (count($i18n) > 0) {
                $manufacturer["i18n"] = $i18n;
            }
            $manufacturer = $this->get_additional_fields('oxmanufacturer', $manufacturer, $oManufacturer);
            $this->resetLanguage($oManufacturer);
            $aResult[] = $manufacturer;
        }
        $this->utf8_encode_deep($aResult);
        return $aResult;
    }

    public function add_manufacturer($data)
    {
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_ADD_MANUFACTURER, array($data));
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oManufacturer = oxNew('oxmanufacturer');

        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oManufacturer->setLanguage($iLangId);
        }

        $blExists = $oManufacturer->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxmanufacturers WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oManufacturer->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;
        foreach ($this->_aManufacturerFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oManufacturer->assign($aAssign);

        $oManufacturer->save();
        $this->updateSEO($oManufacturer->getId());
        $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_ADD_MANUFACTURER, array($data, $oManufacturer));
        return $oManufacturer->getId();
    }

    public function set_manufacturer2products($data)
    {
        ini_set('memory_limit', '1500M');
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        $oManufacturer = null;
        $objids = array();

        foreach ($data as $value) {
            $oManufacturer = null;
            $manufOxid = null;
            if (!$oManufacturer) {
                if ($value->foreignManufacturerId)
                    $manufOxid = $oDb->GetOne('SELECT oxid FROM oxmanufacturers WHERE oxid = ' . $oDb->quote($value->foreignManufacturerId));
                if (!$manufOxid && $value->manufacturerId)
                    $manufOxid = $oDb->GetOne('SELECT oxid FROM oxmanufacturers WHERE wwforeignid = ' . $oDb->quote($value->manufacturerId));
                if ($manufOxid) {
                    $oManufacturer = oxNew("oxmanufacturer");
                    $oManufacturer->load($manufOxid);
                } else
                    return;
            }
            if (!$value->id)
                break;
            $sOxid = null;
            if ($value->foreignId)
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE oxid = ' . $oDb->quote($value->foreignId));
            if (!$sOxid)
                $sOxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE wwforeignid = ' . $oDb->quote($value->id));
            if ($sOxid) {
                $oDb->execute("update oxarticles set oxmanufacturerid=" . $oDb->quote($oManufacturer->getId()) . " where oxid=" . $oDb->quote($sOxid));
                $objids[] = $oDb->quote($sOxid);
            }
        }
        if (count($objids) == 0)
            $oDb->execute("update oxarticles set oxmanufacturerid='' where oxmanufacturerid=" . $oDb->quote($oManufacturer->getId()));
        else
            $oDb->execute("update oxarticles set oxmanufacturerid='' where oxmanufacturerid=" . $oDb->quote($oManufacturer->getId()) . " and not (oxid in (" . implode(",", $objids) . ")) ");
        $this->_clearCatCache();
    }

    public function remove_manufacturer($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oManufacturer = oxNew('oxmanufacturer');

        $blExists = $oManufacturer->load($id);
        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxmanufacturers WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oManufacturer->load($sOxid);
            }
        }
        if ($blExists) {
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::BEFORE_REMOVE_MANUFACTURER, array($oManufacturer));
            $oManufacturer->delete();
        }
    }

    public function get_discounts($page = false, $id = null)
    {
        $oDb = oxDb::getDb();
        if ($id) {
            $sLimit = " where oxdiscount.oxid=" . $oDb->quote($id);
        } else if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxdiscount', $page);
        } else {
            return $this->getCount('oxdiscount');
        }


        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscountFields, null, $this->_aDiscountMultiLangFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxdiscount ' . $sLimit;
        //$sQ = 'SELECT oxid as id, oxtitle as title, oxactive as active, oxshortdesc as description, oxicon as icon FROM oxmanufacturers'.$sLimit;
        $result = $this->getRecords($oDb, $sQ, false);
        $aResult = array();
        foreach ($result as $discount) {
            $oDiscount = oxNew('oxdiscount');
            $oDiscount->load($discount['id']);
            $i18n = $this->get_i18n($oDiscount, "_aDiscountMultiLangFields", "oxdiscount");
            if (count($i18n) > 0) {
                $discount["i18n"] = $i18n;
            }
            $discount = $this->get_additional_fields('oxdiscount', $discount, $oDiscount);

            $this->resetLanguage($oDiscount);
            $discount['countries'] = $this->get_countries2discounts($discount['id']);
            $discount['customerGroups'] = $this->get_customergroups2discounts($discount['id']);
            $aResult[] = $discount;
        }
        $this->utf8_encode_deep($aResult);
        //file_put_contents("log.txt", print_r($aResult, true), FILE_APPEND);
        return $aResult;
    }

    public function get_categories2discounts($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2discount', $page);
        } else {
            return $this->getCount('oxobject2discount', null, " WHERE oxobject2discount.oxtype='oxcategories' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscount2CategoryFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxcategories' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) " . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        $aResult = array();
        foreach ($result as $item) {
            $item['foreignCategory_id'] = $oDb->getOne("select wwforeignid from oxcategories where oxid=" . $oDb->quote($item["category_id"]));
            $aResult[] = $item;
        }
        return $aResult;
    }

    public function get_products2discounts($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2discount', $page);
        } else {
            return $this->getCount('oxobject2discount', null, " WHERE oxobject2discount.oxtype='oxarticles' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscount2ProductFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxarticles' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) " . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        $aResult = array();
        foreach ($result as $item) {
            $item['foreignProduct_id'] = $oDb->getOne("select wwforeignid from oxarticles where oxid=" . $oDb->quote($item["product_id"]));
            $aResult[] = $item;
        }
        return $aResult;
    }

    public function get_customers2discounts($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2discount', $page);
        } else {
            return $this->getCount('oxobject2discount', null, " WHERE oxobject2discount.oxtype='oxuser' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscount2CustomerFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxuser' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) " . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        $aResult = array();
        foreach ($result as $item) {
            $item['foreignCustomer_id'] = $oDb->getOne("select wwforeignid from oxuser where oxid=" . $oDb->quote($item["customer_id"]));
            $aResult[] = $item;
        }
        return $aResult;
    }

    public function get_customergroups2discounts($discountId)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscount2CustomerGroupFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxgroups' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) and oxobject2discount.oxdiscountid=" . $oDb->quote($discountId);
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function get_countries2discounts($discountId)
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aDiscount2CountryFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxcountry' AND oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) and oxobject2discount.oxdiscountid=" . $oDb->quote($discountId);
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function set_discounts($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->add_discount($value);
        }

    }

    public function add_discount($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oDiscount = oxNew('oxdiscount');

        if ($data->lang !== null) {
            $iLangId = $this->_getLanguageId($data->lang);
            if ($iLangId === null)
                return;
            $oDiscount->setLanguage($iLangId);
        }

        $blExists = $oDiscount->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxdiscount WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oDiscount->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;
        foreach ($this->_aDiscountFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid' && !is_object($data->$sRemoteKey))
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
            else if ($data->$sRemoteKey->date)
                $aAssign[$sLocalKey] = $data->$sRemoteKey->date;
        }
        $oDiscount->assign($aAssign);
        if ($oDiscount->oxdiscount__oxactivefrom->value && $oDiscount->oxdiscount__oxactiveto->value && $oDiscount->oxdiscount__oxactivefrom->value != '0000-00-00 00:00:00' && $oDiscount->oxdiscount__oxactiveto->value != '0000-00-00 00:00:00')
            $oDiscount->oxdiscount__oxactive = new oxField(0);
        $sort = $data->sort;
        try {
            if (!$blExists) {
                $rows = $oDb->getAll("select oxid from oxdiscount where oxsort=" . intval($data->sort));
                if (count($rows) > 0)
                    $sort = $oDb->getOne("select max(oxsort) from oxdiscount") + 1;
            } else {
                if (!$data->sort)
                    $sort = $oDiscount->oxdiscount__oxsort->value;
                $rows = $oDb->getAll("select oxid from oxdiscount where oxsort=" . intval($sort) . " and oxid != " . $oDb->quote($oDiscount->getId()));
                if (count($rows) > 0)
                    $sort = $oDb->getOne("select max(oxsort) from oxdiscount") + 1;
            }
            $oDiscount->oxdiscount__oxsort = new oxField($sort);

        } catch (Exception $ex) {

        }
        $oDiscount->save();
        if (class_exists("oxdiscountaccessory")) {

            if ($blExists && $oDiscount->getId())
                $oDb->execute("delete from oxdiscountaccessory where oxdiscountid=" . $oDb->quote($oDiscount->getId()));
            $ind = 0;
            foreach ($data->accessories as $accessory) {
                $oDiscountAccessory = oxNew("oxdiscountaccessory");
                $oDiscountAccessory->setId(str_replace('-', '', $accessory->id));
                $oDiscountAccessory->oxdiscountaccessory__oxdiscountid = new oxField($oDiscount->getId());
                $oDiscountAccessory->oxdiscountaccessory__oxquantity = new oxField($accessory->quantity);
                $oDiscountAccessory->oxdiscountaccessory__oxprice = new oxField($accessory->price);
                $oDiscountAccessory->oxdiscountaccessory__oxcarttype = new oxField($accessory->type);
                $poxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($accessory->accessoryProduct->id));
                if (!$poxid && $accessory->accessoryProduct->foreignId)
                    $poxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE oxid = ' . $oDb->quote($accessory->accessoryProduct->foreignId));
                if (!$poxid)
                    continue;
                $oDiscountAccessory->oxdiscountaccessory__oxarticleid = new oxField($poxid);
                if ($accessory->customerGroups)
                    $oDiscountAccessory->oxdiscountaccessory__oxgroups = new oxField(json_encode($accessory->customerGroups));
                if ($accessory->excludedCustomerGroups)
                    $oDiscountAccessory->oxdiscountaccessory__oxexcludedgroups = new oxField(json_encode($accessory->excludedCustomerGroups));
                $oDiscountAccessory->save();

            }
        }
        return $oDiscount->getId();
    }

    public function remove_discounts($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_discount($value);
        }

    }

    public function remove_discount($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oDiscount = oxNew('oxdiscount');

        $blExists = $oDiscount->load($id);
        if (!$blExists && $data->id) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxdiscount WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oDiscount->load($sOxid);
            }
        }
        if ($blExists) {
            $oDiscount->delete();
        }
    }

    public function get_shipping($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxdeliveryset', $page);
        } else {
            return $this->getCount('oxdeliveryset');
        }
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sFields = $this->toRemoteSqlKeys($this->_aShippingFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxdeliveryset' . $sLimit;

        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function get_voucher_series($page = false, $fromDate = null, $ids = null)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxvoucherseries', $page, $fromDate);
        } else {
            return $this->getCount('oxvoucherseries', $fromDate);
        }
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sFields = $this->toRemoteSqlKeys($this->_aVoucherSerieFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxvoucherseries ';
        if (!is_array($ids))
            $sQ .= $sLimit;
        else {
            if (count($ids) == 0)
                return array();
            for ($i = 0; $i < count($ids); $i++)
                $ids[$i] = $oDb->quote($ids[$i]);
            $ids_sql = implode(",", $ids);
            $sQ .= "WHERE oxid in ($ids_sql)";
        }
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function get_categories2voucherseries($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2discount', $page);
        } else {
            return $this->getCount('oxobject2discount', null, " WHERE oxobject2discount.oxtype='oxcategories' AND oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) ");
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aVoucherSerie2CategoryFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxcategories' AND oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) " . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function get_products2voucherseries($page = false)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxobject2discount', $page);
        } else {
            return $this->getCount('oxobject2discount', null, " WHERE oxobject2discount.oxtype='oxarticles' AND oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) ");
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $sFields = $this->toRemoteSqlKeys($this->_aVoucherSerie2ProductFields);

        $sQ = "SELECT " . $sFields . " FROM oxobject2discount WHERE oxobject2discount.oxtype='oxarticles' AND oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) " . $sLimit;
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function get_vouchers($page = false, $fromDate = null, $ids = null)
    {
        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxvouchers', $page, $fromDate);
        } else {
            return $this->getCount('oxvouchers', $fromDate);
        }
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sFields = $this->toRemoteSqlKeys($this->_aVoucherFields);

        $sQ = 'SELECT ' . $sFields . ' FROM oxvouchers ';
        if (!is_array($ids))
            $sQ .= $sLimit;
        else {
            if (count($ids) == 0)
                return array();
            for ($i = 0; $i < count($ids); $i++)
                $ids[$i] = $oDb->quote($ids[$i]);
            $ids_sql = implode(",", $ids);
            $sQ .= "WHERE oxid in ($ids_sql)";
        }
        $result = $this->getRecords($oDb, $sQ);
        return $result;
    }

    public function set_voucher_series($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->update_voucher_series($value);
        }

    }

    public function update_voucher_series($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oVoucherSerie = oxNew('oxvoucherserie');

        $blExists = $oVoucherSerie->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvoucherseries WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oVoucherSerie->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        foreach ($this->_aVoucherSerieFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $oVoucherSerie->assign($aAssign);
        $oVoucherSerie->oxvoucherseries__oxbegindate = new oxField($data->beginDate->date);
        $oVoucherSerie->oxvoucherseries__oxenddate = new oxField($data->endDate->date);
        $oVoucherSerie->save();
        if (class_exists("oxvoucherserieaccessory")) {

            if ($blExists && $oVoucherSerie->getId())
                $oDb->execute("delete from oxvoucherserieaccessory where oxvoucherserieid=" . $oDb->quote($oVoucherSerie->getId()));

            foreach ($data->accessories as $accessory) {
                $oVoucherSerieAccessory = oxNew("oxvoucherserieaccessory");
                $oVoucherSerieAccessory->setId(str_replace('-', '', $accessory->id));
                $oVoucherSerieAccessory->oxvoucherserieaccessory__oxvoucherserieid = new oxField($oVoucherSerie->getId());
                $oVoucherSerieAccessory->oxvoucherserieaccessory__oxquantity = new oxField($accessory->quantity);
                $oVoucherSerieAccessory->oxvoucherserieaccessory__oxprice = new oxField($accessory->price);
                $poxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE WWFOREIGNID = ' . $oDb->quote($accessory->accessoryProduct->id));
                if (!$poxid && $accessory->accessoryProduct->foreignId)
                    $poxid = $oDb->GetOne('SELECT oxid FROM oxarticles WHERE oxid = ' . $oDb->quote($accessory->accessoryProduct->foreignId));
                if (!$poxid)
                    continue;
                $oVoucherSerieAccessory->oxvoucherserieaccessory__oxarticleid = new oxField($poxid);
                $oVoucherSerieAccessory->save();

            }
        }
        return $oVoucherSerie->getId();
    }

    public function remove_vouchers($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        foreach ($data as $value) {
            $oxid = $oDb->getOne("select oxid from oxvouchers where oxid=" . $oDb->quote($value->foreignId));

            if (!$oxid) {
                //Check if we can resolve the category by it's foreign id
                $oxid = $oDb->GetOne('SELECT oxid FROM oxvouchers WHERE WWFOREIGNID = ' . $oDb->quote($value->id));
                if (!$oxid) {
                    continue;
                }
            }
            $oDb->execute('delete from oxvouchers where oxid=' . $oDb->quote($oxid) . " and (oxdateused IS NULL OR oxdateused='0000-00-00')");
        }
    }

    public function remove_all_vouchers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->remove_all_voucher($value);
        }
    }

    public function remove_all_voucher($data)
    {
        $oDb = oxDb::getDb();
        $oxid = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($data->foreignVoucherSerieId));

        if (!$oxid) {
            //Check if we can resolve the category by it's foreign id
            $oxid = $oDb->GetOne('SELECT oxid FROM oxvoucherseries WHERE WWFOREIGNID = ' . $oDb->quote($data->voucherserie_id));
            if (!$oxid) {
                return;
            }
        }
        $oDb->execute('delete from oxvouchers where oxvoucherserieid=' . $oDb->quote($oxid) . " and (oxdateused IS NULL OR oxdateused='0000-00-00')");
    }

    public function remove_voucherseries($data)
    {
        $this->utf8_decode_deep($data);

        foreach ($data as $value) {
            $this->remove_voucherserie($value);
        }
    }

    public function remove_voucherserie($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();
        $oVoucherSerie = oxNew('oxvoucherserie');

        $blExists = $oVoucherSerie->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvoucherseries WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oVoucherSerie->load($sOxid);
            }
        }
        if ($blExists)
            $oVoucherSerie->delete();
    }

    public function set_categories2voucherseries($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($value->category_id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($value->foreignCategoryId));
            if ($catid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($catid) . " and oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_category2voucherseries($value);
        }
    }

    public function add_category2voucherseries($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->vouchers as $voucher) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($voucher->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($voucher->foreignId));
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($data->category_id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($data->foreignCategoryId));
            if (!$catid)
                continue;
            $aAssign["oxobjectid"] = $catid;
            $aAssign["oxtype"] = "oxcategories";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_products2voucherseries($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($value->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($value->foreignProductId));
            if ($prodid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($prodid) . " and oxobject2discount.oxdiscountid in (select oxid from oxvoucherseries where oxvoucherseries.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_product2voucherseries($value);
        }
    }

    public function add_product2voucherseries($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->vouchers as $voucher) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($voucher->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($voucher->foreignId));
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($data->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($data->foreignProductId));
            if (!$prodid)
                continue;
            $aAssign["oxobjectid"] = $prodid;
            $aAssign["oxtype"] = "oxarticles";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_voucherseries2categories($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_voucherserie2categories($value);
        }
    }

    public function add_voucherserie2categories($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->categories as $category) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($data->voucherserie_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($data->foreignVoucherSerieId));
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($category->id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($category->foreignCategoryId));
            if (!$catid)
                continue;
            $aAssign["oxobjectid"] = $catid;
            $aAssign["oxtype"] = "oxcategories";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_voucherseries2categories($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($value->voucherserie_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($value->foreignVoucherSerieId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxcategories'");
        }
    }

    public function remove_voucherseries2products($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($value->voucherserie_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($value->foreignVoucherSerieId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxarticles'");
        }
    }

    public function set_voucherseries2products($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_voucherserie2products($value);
        }
    }

    public function add_voucherserie2products($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->products as $product) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($data->voucherserie_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($data->foreignVoucherSerieId));
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($product->id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($product->foreignProductId));
            if (!$prodid)
                continue;
            $aAssign["oxobjectid"] = $prodid;
            $aAssign["oxtype"] = "oxarticles";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_voucherseries2customergroups($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_voucherserie2customergroups($value);
        }
    }

    public function add_voucherserie2customergroups($data)
    {

        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        $aAssign = array();
        $conf = agConfig::getInstance();
        $voucherSerieId = $oDb->getOne("select oxid from oxvoucherseries where wwforeignid=" . $oDb->quote($data->voucherserie_id));
        if (!$voucherSerieId)
            $voucherSerieId = $oDb->getOne("select oxid from oxvoucherseries where oxid=" . $oDb->quote($data->foreignVoucherSerieId));
        if ($voucherSerieId) {
            $oVoucherSerie = oxNew("oxvoucherserie");
            $oVoucherSerie->load($voucherSerieId);
            $oDb->execute("delete from oxobject2group where oxobjectid=" . $oDb->quote($voucherSerieId));
        } else
            return;
        foreach ($data->customerGroups as $customerGroup) {
            $groupid = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($customerGroup->id));
            if (!$groupid)
                $groupid = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($customerGroup->foreignCustomerGroupId));
            if (!$groupid)
                continue;
            $oObject2Group = oxNew("oxbase");
            $oObject2Group->init("oxobject2group");
            $aAssign["oxobjectid"] = $voucherSerieId;
            $aAssign["oxgroupsid"] = $groupid;
            $aAssign["oxtimestamp"] = date('Y-m-d H:i:s');
            $aAssign["oxshopid"] = $conf->getShopId();
            $oObject2Group->assign($aAssign);
            $oObject2Group->save();
        }
        return true;
    }

    public function update_vouchers_usage($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->update_voucher_usage($value);
        }

    }

    public function set_vouchers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->update_vouchers($value);
        }

    }

    public function update_vouchers($data)
    {
        $id = $data->foreignId;
        $oDb = oxDb::getDb();

        $oVoucher = oxNew('oxvoucher');

        $blExists = $oVoucher->load($id);

        if (!$blExists) {
            //Check if we can resolve the category by it's foreign id
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvouchers WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
            if ($sOxid) {
                $blExists = $oVoucher->load($sOxid);
            }
        }

        $aAssign = array();
        $aAssign['wwforeignid'] = $data->id;

        foreach ($this->_aVoucherFields as $sLocalKey => $sRemoteKey) {
            if ($sLocalKey != 'oxid' && $sLocalKey != 'wwforeignid')
                $aAssign[$sLocalKey] = $data->$sRemoteKey;
        }
        $voucherserie = $data->voucherserie;
        $oVoucherSerie = oxNew("oxvoucherserie");
        if ($blExists) {
            /*if (!$aAssign["oxdateused"] && $oVoucher->oxvouchers__oxdateused->value && $oVoucher->oxvouchers__oxdateused->value != '0000-00-00 00:00:00')
                {
                    $aAssign["oxdateused"] = $oVoucher->oxvouchers__oxdateused->value;
                    $aAssign["oxuserid"] = $oVoucher->oxvouchers__oxuserid->value;
                    $aAssign["oxorderid"] = $oVoucher->oxvouchers__oxorderid->value;
                }*/
        }
        //file_put_contents("log.txt", print_r($data, true),FILE_APPEND);
        $blExists = $oVoucherSerie->load($voucherserie->foreignId);
        if (!$blExists) {
            $sOxid = $oDb->GetOne('SELECT oxid FROM oxvoucherseries WHERE WWFOREIGNID = ' . $oDb->quote($voucherserie->id));
            if (!$sOxid)
                return null;
        } else
            $sOxid = $voucherserie->foreignId;
        $aAssign['oxvoucherserieid'] = $sOxid;
        if (!$aAssign['oxuserid'] && $aAssign['oxdateused'])
            $aAssign['oxuserid'] = 'wwanonymous';
        $oVoucher->assign($aAssign);
        $oVoucher->save();

        return $oVoucher->getId();
    }

    public function update_voucher_usage($data)
    {
        $oDb = oxDb::getDb();

        $oVoucher = oxNew('oxvoucher');
        $sOxid = $oDb->GetOne('SELECT oxid FROM oxvouchers WHERE WWFOREIGNID = ' . $oDb->quote($data->id));
        if ($sOxid) {
            $blExists = $oVoucher->load($sOxid);
        }
        if ($blExists) {
            $oVoucher->oxvouchers__oxdateused = new oxField($data->usedDate);
            $oVoucher->save();
        }

    }

    public function set_categories2discounts($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($value->category_id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($value->foreignCategoryId));
            if ($catid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($catid) . " and oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_category2discount($value);
        }
    }

    public function add_category2discount($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->discounts as $discount) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($discount->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($discount->foreignId));
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($data->category_id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($data->foreignCategoryId));
            if (!$catid)
                continue;
            $aAssign["oxobjectid"] = $catid;
            $aAssign["oxtype"] = "oxcategories";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_products2discounts($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($value->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($value->foreignProductId));
            if ($prodid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($prodid) . " and oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_product2discounts($value);
        }
    }

    public function add_product2discounts($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->discounts as $discount) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($discount->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($discount->foreignId));
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($data->product_id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($data->foreignProductId));
            if (!$prodid)
                continue;
            $aAssign["oxobjectid"] = $prodid;
            $aAssign["oxtype"] = "oxarticles";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_customers2discounts($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $custid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($value->customer_id));
            if (!$custid)
                $custid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($value->foreignCustomerId));
            if ($custid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($custid) . " and oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_customer2discounts($value);
        }
    }

    public function add_customer2discounts($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->discounts as $discount) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($discount->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($discount->foreignId));
            $custid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($data->customer_id));
            if (!$custid)
                $custid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($data->foreignCustomerId));
            if (!$custid)
                continue;
            $aAssign["oxobjectid"] = $custid;
            $aAssign["oxtype"] = "oxuser";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_countries2discounts($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $countryid = $oDb->getOne("select oxid from oxcountry where wwforeignid=" . $oDb->quote($value->country_id));
            if (!$countryid)
                $countryid = $oDb->getOne("select oxid from oxcountry where oxid=" . $oDb->quote($value->foreignCountryId));
            if ($countryid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($countryid) . " and oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_country2discounts($value);
        }
    }

    public function add_country2discounts($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->discounts as $discount) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($discount->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($discount->foreignId));
            $countryid = $oDb->getOne("select oxid from oxcountry where wwforeignid=" . $oDb->quote($data->country_id));
            if (!$countryid)
                $countryid = $oDb->getOne("select oxid from oxcountry where oxid=" . $oDb->quote($data->foreignCountryId));
            if (!$countryid)
                continue;
            $aAssign["oxobjectid"] = $countryid;
            $aAssign["oxtype"] = "oxcountry";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_customergroups2discounts($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();
        //file_put_contents('log.txt',print_r($data,true),FILE_APPEND);
        foreach ($data as $value) {
            $groupid = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($value->group_id));
            if (!$groupid)
                $groupid = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($value->foreignGroupId));
            if ($groupid)
                $oDb->execute("delete from oxobject2discount where oxobjectid=" . $oDb->quote($groupid) . " and oxobject2discount.oxdiscountid in (select oxid from oxdiscount where oxdiscount.oxid=oxobject2discount.oxdiscountid) ");
        }
        foreach ($data as $value) {
            $this->add_customergroup2discounts($value);
        }
    }

    public function add_customergroup2discounts($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->discounts as $discount) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($discount->id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($discount->foreignId));
            $groupid = $oDb->getOne("select oxid from oxcountry where wwforeignid=" . $oDb->quote($data->group_id));
            if (!$groupid)
                $groupid = $oDb->getOne("select oxid from oxcountry where oxid=" . $oDb->quote($data->foreignGroupId));
            if (!$groupid)
                continue;
            $aAssign["oxobjectid"] = $groupid;
            $aAssign["oxtype"] = "oxgroups";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function set_discounts2categories($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_discount2categories($value);
        }
    }

    public function add_discount2categories($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->categories as $category) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($data->discount_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($data->foreignDiscountId));
            $catid = $oDb->getOne("select oxid from oxcategories where wwforeignid=" . $oDb->quote($category->id));
            if (!$catid)
                $catid = $oDb->getOne("select oxid from oxcategories where oxid=" . $oDb->quote($category->foreignCategoryId));
            if (!$catid)
                continue;
            $aAssign["oxobjectid"] = $catid;
            $aAssign["oxtype"] = "oxcategories";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_discounts2categories($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($value->discount_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($value->foreignDiscountId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxcategories'");
        }
    }

    public function set_discounts2products($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_discount2products($value);
        }
    }

    public function add_discount2products($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->products as $product) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($data->discount_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($data->foreignDiscountId));
            $prodid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($product->id));
            if (!$prodid)
                $prodid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($product->foreignProductId));
            if (!$prodid)
                continue;
            $aAssign["oxobjectid"] = $prodid;
            $aAssign["oxtype"] = "oxarticles";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_discounts2products($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($value->discount_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($value->foreignDiscountId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxarticles'");
        }
    }

    public function set_discounts2customers($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_discount2customers($value);
        }
    }

    public function add_discount2customers($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->customers as $customer) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($data->discount_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($data->foreignDiscountId));
            $custid = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($customer->id));
            if (!$custid)
                $custid = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($customer->foreignCustomerId));
            if (!$custid)
                continue;
            $aAssign["oxobjectid"] = $custid;
            $aAssign["oxtype"] = "oxuser";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_discounts2customers($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($value->discount_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($value->foreignDiscountId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxuser'");
        }
    }

    public function set_discounts2customergroups($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_discount2customergroups($value);
        }
    }

    public function add_discount2customergroups($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();
        foreach ($data->customerGroups as $customerGroup) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($data->discount_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($data->foreignDiscountId));
            $groupid = $oDb->getOne("select oxid from oxgroups where wwforeignid=" . $oDb->quote($customerGroup->id));
            if (!$groupid)
                $groupid = $oDb->getOne("select oxid from oxgroups where oxid=" . $oDb->quote($customerGroup->foreignCustomerGroupId));
            if (!$groupid)
                continue;
            $aAssign["oxobjectid"] = $groupid;
            $aAssign["oxtype"] = "oxgroups";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_discounts2customergroups($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($value->discount_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($value->foreignDiscountId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxgroups'");
        }
    }

    public function set_discounts2countries($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $this->add_discount2countries($value);
        }
    }

    public function add_discount2countries($data)
    {

        $oDb = oxDb::getDb();

        $aAssign = array();

        foreach ($data->countries as $country) {
            $oObject2Discount = oxNew("oxbase");
            $oObject2Discount->init("oxobject2discount");
            $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($data->discount_id));
            if (!$aAssign["oxdiscountid"])
                $aAssign["oxdiscountid"] = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($data->foreignDiscountId));
            $countryid = $oDb->getOne("select oxid from oxcountry where oxid=" . $oDb->quote($country->foreignCountryId));
            if (!$countryid)
                continue;
            $aAssign["oxobjectid"] = $countryid;
            $aAssign["oxtype"] = "oxcountry";
            $oObject2Discount->assign($aAssign);
            $oObject2Discount->save();
        }
        return true;
    }

    public function remove_discounts2countries($data)
    {
        $this->utf8_decode_deep($data);
        $oDb = oxDb::getDb();

        foreach ($data as $value) {
            $vsid = $oDb->getOne("select oxid from oxdiscount where wwforeignid=" . $oDb->quote($value->discount_id));
            if (!$vsid)
                $vsid = $oDb->getOne("select oxid from oxdiscount where oxid=" . $oDb->quote($value->foreignDiscountId));
            if ($vsid)
                $oDb->execute("delete from oxobject2discount where oxdiscountid=" . $oDb->quote($vsid) . " and oxtype='oxcountry'");
        }
    }

    public function get_orders_ids_last_day()
    {
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('warexoUseShopId'))
            $rows = $oDb->getAll("select oxid from oxorder where oxorderdate > DATE_SUB(NOW(), INTERVAL 1 DAY) AND oxorderdate < DATE_SUB(NOW(), INTERVAL 15 MINUTE ) AND oxshopid=" . $oDb->quote($conf->getShopId()) . "  order by oxorderdate asc");
        else
            $rows = $oDb->getAll("select oxid from oxorder where oxorderdate > DATE_SUB(NOW(), INTERVAL 1 DAY) AND oxorderdate < DATE_SUB(NOW(), INTERVAL 15 MINUTE ) order by oxorderdate asc");
        $ids = array();
        foreach ($rows as $row)
            $ids[] = $row[0];
        return $ids;
    }

    public function refresh_order_timestamps($ids)
    {
        $oDb = oxDb::getDb();
        for ($i = 0; $i < count($ids); $i++)
            $ids[$i] = $oDb->quote($ids[$i]);
        file_put_contents('pendingorders.log', "update oxorder set oxtimestamp=NOW() WHERE OXID in (" . implode(",", $ids) . ")\n", FILE_APPEND);
        $oDb->execute("update oxorder set oxtimestamp=NOW() WHERE OXID in (" . implode(",", $ids) . ")");
    }

    public function get_orders($page = false, $fromDate = null, $ids = null)
    {

        if (is_numeric($page)) {
            $sLimit = $this->getLimit('oxorder', $page, $fromDate);
        } else {
            return $this->getCount('oxorder', $fromDate);
        }

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);

        $sFields = $this->toRemoteSqlKeys($this->_aOrderFields);
        $sWhere = '';
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('warexoUseShopId'))
            $sWhere = ' where oxorder.oxshopid = ' . $oDb->quote($conf->getShopId());
        //$filterSql = $this->get_filter_sql('oxorder');
        if ($ids) {
            for ($i = 0; $i < count($ids); $i++)
                $ids[$i] = $oDb->quote($ids[$i]);
            $sQ = "SELECT " . $sFields . " FROM oxorder WHERE OXID in (" . implode(",", $ids) . ")";
        } else if ($sWhere)
            $sQ = 'SELECT ' . $sFields . ' FROM oxorder ' . $sWhere . ' ' . str_replace('where', 'and', $sLimit);
        else
            $sQ = 'SELECT ' . $sFields . ' FROM oxorder ' . $sWhere . ' ' . $sLimit;
        //file_put_contents(getShopBasePath()."wawi/log.txt",$sQ);
        $orders = $this->getRecords($oDb, $sQ);
        $result = array();
        $i = 0;
        $dDefaultVat = agConfig::getInstance()->getConfigParam('dDefaultVAT');

        foreach ($orders as $order) {
            $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
            $sQ = "SELECT oxid as id, 
                              oxartid as product, 
                              oxartnum as sku, 
                              oxtitle as productTitle, 
                              oxselvariant as variantTitle, 
                              oxamount as quantity, 
                              IF(oxvat IS NOT NULL, oxvat, $dDefaultVat) as vat, 
                              oxbprice as price, 
                              oxweight as weight, 
                              oxpersparam as productParameter, 
                              oxstorno as storno" .
                $this->_sOfferItemFields .
                " FROM oxorderarticles WHERE oxorderid = " . $oDb->quote($order['id']);

            $order['items'] = $this->getRecords($oDb, $sQ);
            $oOrder = oxNew("oxorder");
            $oOrder->load($order['id']);
            $order['billingCountry'] = $oDb->getOne("select oxisoalpha3 from oxcountry where oxid=" . $oDb->quote($order['billingCountry']));
            if ($order['billingState'])
                $order['billingState'] = $oDb->getOne("select oxisoalpha2 from oxstates where oxid=" . $oDb->quote($order['billingState']));
            if ($order['shippingCountry'])
                $order['shippingCountry'] = $oDb->getOne("select oxisoalpha3 from oxcountry where oxid=" . $oDb->quote($order['shippingCountry']));
            if ($order['shippingState'])
                $order['shippingState'] = $oDb->getOne("select oxisoalpha2 from oxstates where oxid=" . $oDb->quote($order['shippingState']));
            $order['giftCardText'] = str_replace("&#13;&#10;", "\r\n", $order['giftCardText']);
            $order['giftCardText'] = str_replace("&#10;", "\n", $order['giftCardText']);
            $order['giftCardText'] = str_replace("&#13;", "", $order['giftCardText']);

            $oGiftCard = $oOrder->getGiftCard();
            $order['giftCardType'] = $oGiftCard->oxwrapping__oxname->value;
            if (strtoupper($order['billingSalutation']) == 'MR' || strtoupper($order['billingSalutation']) == 'MRS')
                $order['billingSalutation'] = strtoupper($order['billingSalutation']);
            if (strtoupper($order['shippingSalutation']) == 'MR' || strtoupper($order['shippingSalutation']) == 'MRS')
                $order['shippingSalutation'] = strtoupper($order['shippingSalutation']);
            $newoitems = array();
            foreach ($order['items'] as $oitem) {
                $newoitem = $oitem;
                $newoitem["foreignproduct"] = $oDb->getOne("select wwforeignid from oxarticles where oxid=" . $oDb->quote($oitem['product']));
                if ($order["nettoMode"]) {
                    $newoitem["price"] = $oDb->getOne("select oxnprice from oxorderarticles where oxid=" . $oDb->quote($oitem['id']));
                }
                $wrapid = $oDb->getOne("select oxwrapid from oxorderarticles where oxid=" . $oDb->quote($oitem['id']));
                $newoitem["productstock"] = $oDb->getOne("select oxstock from oxarticles where oxid=" . $oDb->quote($oitem['product']));
                if ($wrapid) {
                    $oWrapping = oxNew("oxwrapping");
                    $oWrapping->load($wrapid);
                    $newoitem["wrapTitle"] = $oWrapping->oxwrapping__oxname->value;
                }
                $newoitems[] = $newoitem;
            }
            if ($oOrder->oxorder__oxuserid->value) {
                $foreignCustomerId = $oDb->getOne("select WWFOREIGNID from oxuser where oxid=" . $oDb->quote($oOrder->oxorder__oxuserid->value));
                if ($foreignCustomerId)
                    $order['foreignCustomerId'] = $foreignCustomerId;
            }
            $order['items'] = $newoitems;
            $order['items'] = $this->get_additional_fields('oxorderitem', $order['items']);
            $order = $this->get_additional_fields('oxorder', $order, $oOrder);
            $order['shippingName'] = $this->_prepareForUtf8($oDb->getOne("select oxtitle from oxdeliveryset where oxid=" . $oDb->quote($order['shipping'])));
            $order['paymentName'] = $this->_prepareForUtf8($oDb->getOne("select oxdesc from oxpayments where oxid=" . $oDb->quote($order['paymentId'])));
            $oPayment = oxNew("oxpayment");
            $oPayment->load($order['paymentId']);
            $i18n = $this->get_i18n($oPayment, "_aPaymentMultiLangFields", "oxpayments");
            if (count($i18n) > 0) {
                $order['paymentNames'] = array("i18n" => $i18n);
            }
            $paymentFields = $this->_getPaymentType($oOrder);
            if ($paymentFields->aDynValues) {
                $order['paymentFields'] = array();
                foreach ($paymentFields->aDynValues as $dynValue) {
                    $name = $this->_aPaymentFieldMap[$dynValue->name];
                    if (!$name)
                        $name = $dynValue->name;
                    $order['paymentFields'][] = array('ident' => $name, 'value' => $dynValue->value);
                }
            }
            if ($order["nettoMode"])
                $order["calcPlusTax"] = true;
            $order['language'] = $this->getLanguageLocale($order['language']);
            if ($order['paymentTransactionId2'] && $order['paymentTransactionId'])
                $order['paymentTransactionId'] = $order['paymentTransactionId2'];
            if ($oOrder->oxorder__jagamazonorderreferenceid->value)
                $order['paymentTransactionId'] = $oOrder->oxorder__jagamazonorderreferenceid->value;
            if ($oOrder->oxorder__bestitamazonorderreferenceid->value && !$oOrder->oxorder__oxtransid->value)
                $order['paymentTransactionId'] = $oOrder->oxorder__bestitamazonorderreferenceid->value;
            if ($oOrder->oxorder__fcpotxid->value && !$oOrder->oxorder__oxtransid->value)
                $order['paymentTransactionId'] = $oOrder->oxorder__fcpotxid->value;
            if ($order['paymentCost'] < 0 && $order["nettoMode"] && $oOrder->oxorder__oxpayvat->value) {
                $order["paymentCost"] = $order['paymentCost'] / (1 + $oOrder->oxorder__oxpayvat->value / 100);
            }
            /*//Add Shipping if any
				$this->_pushVirtualOrderItem($order,'oxdelcost','oxdelvat','Versandkosten');
				//Add pay cost if any
				$this->_pushVirtualOrderItem($order,'oxpaycost','oxpayvat','Zahlungsartkosten');
				//Add wrap cost if any
				$this->_pushVirtualOrderItem($order,'oxwrapcost','oxwrapvat','Geschenkverpackungskosten');*/
            //Add gift card cost if any
            //$this->_pushVirtualOrderItem($order,'oxgiftcardcost','oxgiftcardvat','Karte');

            //Map payment type against remote payment type
            $sPaymentId = $order['paymentId'];
            if (isset($this->_aPaymentMap[$sPaymentId])) {
                $order['paymentId'] = $this->_aPaymentMap[$sPaymentId];
            } else {
                //unset($order['paymentId']);
            }
            $oVoucherList = oxNew("oxvoucherlist");
            $oVoucherList->selectString("select * from oxvouchers where oxorderid=" . $oDb->quote($order["id"]));
            $vouchers = array();
            $voucherSeries = array();
            foreach ($oVoucherList as $oVoucher) {
                $vouchers[] = $oVoucher->getId();
                if (!in_array($oVoucher->oxvouchers__oxvoucherserieid->value, $voucherSeries))
                    $voucherSeries[] = $oVoucher->oxvouchers__oxvoucherserieid->value;
            }
            if (count($vouchers) > 0)
                $order["vouchers"] = $this->get_vouchers(0, null, $vouchers);
            if (count($voucherSeries) > 0)
                $order["voucherseries"] = $this->get_voucher_series(0, null, $voucherSeries);
            $result[] = $order;
        }

        return $result;
    }

    protected function _getPaymentType($oOrder)
    {
        if (!($oUserPayment = $oOrder->getPaymentType()) && $oOrder->oxorder__oxpaymenttype->value) {
            $oPayment = oxNew("oxPayment");
            if ($oPayment->load($oOrder->oxorder__oxpaymenttype->value)) {
                // in case due to security reasons payment info was not kept in db
                $oUserPayment = oxNew("oxUserPayment");
                $oUserPayment->oxpayments__oxdesc = new oxField($oPayment->oxpayments__oxdesc->value);
            }
        }

        return $oUserPayment;
    }

    public function set_offers($data)
    {
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->set_offer($value);
        }
    }

    public function delete_offer($data)
    {
        $oDb = oxDb::getDb();
        if (!$data->customer)
            return;
        if ($data->customer->foreignId)
            $sUserId = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($data->customer->foreignId));
        if (!$sUserId)
            $sUserId = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($data->customer->id));
        if (!$sUserId) {
            return;
        }

        $oUser = oxNew("oxuser");
        if (!$oUser->load($sUserId))
            return;
        $oUserBasket = $oUser->getBasket('savedbasket');
        $oUserBasket->delete();
        $oUser->save();
    }

    public function set_offer($data)
    {
        $oDb = oxDb::getDb();
        if (!$data->customer)
            return;
        if ($data->customer->foreignId)
            $sUserId = $oDb->getOne("select oxid from oxuser where oxid=" . $oDb->quote($data->customer->foreignId));
        if (!$sUserId)
            $sUserId = $oDb->getOne("select oxid from oxuser where wwforeignid=" . $oDb->quote($data->customer->id));
        if (!$sUserId) {
            return;
        }

        $oUser = oxNew("oxuser");
        if (!$oUser->load($sUserId))
            return;

        $oUserBasket = $oUser->getBasket('savedbasket');

        foreach ($data->offeritems as $id => $offeritem) {
            if ($offeritem->product) {
                $artid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($offeritem->product->id));
                if (!$artid)
                    $artid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($offeritem->product->foreignId));
                if ($artid) {
                    $oUserBasket->addItemToBasket($artid, $offeritem->quantity, null, false, $offeritem->productParameter ? unserialize($offeritem->productParameter) : null);
                }
            }
        }
        $oUser->oxuser__wwupdatebasket = new oxField(1);
        $oUser->save();
    }

    public function set_orders($data)
    {
        $conf = agConfig::getInstance();
        if ($conf->getConfigParam('bWAWINotUpdateOrders'))
            return;
        $this->utf8_decode_deep($data);
        foreach ($data as $value) {
            $this->update_order($value);
        }

    }

    public function update_order($data)
    {
        $oOrder = oxNew("oxOrder");
        $oDb = oxDb::getDb();
        if ($oOrder->load($data->foreignId)) {
            if ($data->transactionStatus == "ERROR" && $oOrder->oxorder__oxtransstatus->value != "ERROR")
                return;
            $oOrder->oxorder__oxstorno = new oxField($data->storno);
            $oOrder->oxorder__oxfolder = new oxField($data->status);
            $oOrder->oxorder__oxsenddate = new oxField($data->shippingDate);
            $paidDate = $oOrder->oxorder__oxpaid->value;
            $oOrder->oxorder__oxpaid = new oxField($data->paidDate);

            if (!($data->transactionStatus == "NOT_FINISHED" && $oOrder->oxorder__oxtransstatus->value != "NOT_FINISHED"))
                $oOrder->oxorder__oxtransstatus = new oxField($data->transactionStatus);
            $oOrder->oxorder__oxbillnr = new oxField($data->invoiceNumber);
            $oOrder->oxorder__oxbilldate = new oxField($data->invoiceDate);
            $oOrder->oxorder__oxtrackcode = new oxField($data->trackingCode);
            $oOrder->oxorder__oxbillemail = new oxField($data->billingEMail);
            $oOrder->oxorder__oxbillsal = new oxField($data->billingSalutation);
            $oOrder->oxorder__oxbillcompany = new oxField($data->billingCompany);
            $oOrder->oxorder__oxbillfname = new oxField($data->billingFirstName);
            $oOrder->oxorder__oxbilllname = new oxField($data->billingLastName);
            $oOrder->oxorder__oxbillstreet = new oxField($data->billingStreet);
            $oOrder->oxorder__oxbillstreetnr = new oxField("");
            $oOrder->oxorder__oxbillcity = new oxField($data->billingCity);
            $oOrder->oxorder__oxbillzip = new oxField($data->billingZip);
            $oOrder->oxorder__oxbillcountryid = new oxField($data->billingCountry);
            $oOrder->oxorder__oxbillstateid = new oxField($data->billingState);
            $oOrder->oxorder__oxbillfon = new oxField($data->billingPhone);
            $oOrder->oxorder__oxbillfax = new oxField($data->billingFax);
            $oOrder->oxorder__oxbilladdinfo = new oxField($data->billingAdditionalInfo);

            $oOrder->oxorder__oxdelsal = new oxField($data->shippingSalutation);
            $oOrder->oxorder__oxdelcompany = new oxField($data->shippingCompany);
            $oOrder->oxorder__oxdelfname = new oxField($data->shippingFirstName);
            $oOrder->oxorder__oxdellname = new oxField($data->shippingLastName);
            $oOrder->oxorder__oxdelstreet = new oxField($data->shippingStreet);
            $oOrder->oxorder__oxdelstreetnr = new oxField("");
            $oOrder->oxorder__oxdelcity = new oxField($data->shippingCity);
            $oOrder->oxorder__oxdelzip = new oxField($data->shippingZip);
            $oOrder->oxorder__oxdelcountryid = new oxField($data->shippingCountry);
            $oOrder->oxorder__oxdelstateid = new oxField($data->shippingState);
            $oOrder->oxorder__oxdelfon = new oxField($data->shippingPhone);
            $oOrder->oxorder__oxdelfax = new oxField($data->shippingFax);
            $oOrder->oxorder__oxdeladdinfo = new oxField($data->shippingAdditionalInfo);

            $oOrder->oxorder__oxbilldate = new oxField($data->invoiceDate);

            $oOrder->oxorder__oxtotalordersum = new oxField($data->orderTotal);
            $oOrder->oxorder__oxdelcost = new oxField($data->deliveryCost);
            $oOrder->oxorder__oxpaycost = new oxField($data->paymentCost);
            $oOrder->oxorder__oxwrapcost = new oxField($data->wrapCost);
            $oOrder->oxorder__oxgiftcardcost = new oxField($data->giftCardCost);
            $oOrder->oxorder__oxdiscount = new oxField($data->discount);
            $oOrder->oxorder__oxvoucherdiscount = new oxField($data->voucherDiscount);
            $oOrder->oxorder__oxtotalbrutsum = new oxField($data->orderArticleBrutto);
            $oOrder->oxorder__oxtotalnetsum = new oxField($data->orderArticleNetto);
            $oOrder->oxorder__oxdeltype = new oxField($data->shippingId);
            $flipped = array_flip($this->_aPaymentMap);
            if (isset($flipped[$data->paymentId]))
                $data->paymentId = $flipped[$data->paymentId];
            $oOrder->oxorder__oxpaymenttype = new oxField($data->paymentId);
            $oOrder->resetTimestamp = true;
            if (strpos($oOrder->oxorder__oxpaymenttype->value, "fcpo") !== FALSE && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            } else if (strpos($oOrder->oxorder__oxpaymenttype->value, "bestitamazon") !== FALSE && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            } else if (strpos($oOrder->oxorder__oxpaymenttype->value, "oxidamazon") !== FALSE && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            } else if ($oOrder->oxorder__agpaypalcaptureid->value && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            } else if (strpos($oOrder->oxorder__oxpaymenttype->value, "oscpaypal") !== FALSE && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            } else if (strpos($oOrder->oxorder__oxpaymenttype->value, "mollie") !== FALSE && $paidDate && $paidDate != '0000-00-00 00:00:00' && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $oOrder->oxorder__oxpaid = new oxField($paidDate);
                $oOrder->resetTimestamp = false;
            }
            //$oOrder->oxorder__oxtransid = new oxField($data->paymentTransactionId);
            $oOrder->save();
            file_put_contents('orders.log', 'update order ' . $oOrder->oxorder__oxordernr->value . ", paid date " . $oOrder->oxorder__oxpaid->value . "\n", FILE_APPEND);
            if (strpos($oOrder->oxorder__oxpaymenttype->value, "fcpo") !== FALSE && method_exists($oOrder, "fcpoGetStatus") && ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' || !$oOrder->oxorder__oxpaid->value)) {
                $aStatus = $oOrder->fcpoGetStatus();
                $last_payment = 0;
                foreach ($aStatus as $stat) {
                    $payment = $stat->fcpotransactionstatus__fcpo_receivable->value - $stat->fcpotransactionstatus__fcpo_balance->value;
                    if (abs($last_payment - $payment) > 0.0001 && $payment - $last_payment > 0.0001) {
                        $tm = $stat->fcpotransactionstatus__fcpo_timestamp->value;
                        if (!$tm)
                            $tm = $stat->fcpotransactionstatus__fcpo_txtime->value;
                        file_put_contents('orders.log', 'check payone order ' . $oOrder->oxorder__oxordernr->value . ", paid date " . $tm . "\n", FILE_APPEND);
                        $oOrder->resetTimestamp = false;
                        $oOrder->oxorder__oxpaid = new oxField($tm);
                        $oOrder->save();
                        break;
                    }
                    $last_payment = $payment;
                }
            }
            $conf = agConfig::getInstance();
            if ($conf->getConfigParam('bWAWINotReplaceOrderItems'))
                return;
            $orderitemsids = array();
            foreach ($oOrder->getOrderArticles() as $oOrderArticle) {
                $orderitemsids[$oOrderArticle->getId()] = false;
            }
            foreach ($data->orderitems as $id => $orderitem) {
                $oOrderArticle = oxNew("oxorderarticle");
                /*if ($oOrderArticle->load($id))
					{
						$oOrderArticle->oxorderarticles__oxstorno = new oxField($orderitem->storno);
                        $oOrderArticle->oxorderarticles__oxselvariant = new oxField($orderitem->variantTitle);
                        $oOrderArticle->oxorderarticles__oxamount = new oxField($orderitem->quantity);
						$oOrderArticle->save();
					}
                    else*/
                {
                    if ($oOrderArticle->load($id))
                        $orderitemsids[$id] = true;
                    else {
                        $foreignId = $oDb->getOne("select oxid from oxorderarticles where oxid=" . $oDb->quote(str_replace("-", "", $id)));
                        if ($foreignId) {
                            $id = $foreignId;
                            $oOrderArticle->load($id);
                            $orderitemsids[$id] = true;
                        }

                    }
                    $oOrderArticle->oxorderarticles__oxorderid = new oxField($oOrder->oxorder__oxid->value);
                    $oOrderArticle->oxorderarticles__oxstorno = new oxField($orderitem->storno);
                    $oOrderArticle->oxorderarticles__oxselvariant = new oxField($orderitem->variantTitle);
                    $oOrderArticle->oxorderarticles__oxamount = new oxField($orderitem->quantity);
                    $oOrderArticle->oxorderarticles__oxtitle = new oxField($orderitem->productTitle);
                    $oOrderArticle->oxorderarticles__oxartnum = new oxField($orderitem->sku);
                    $oOrderArticle->oxorderarticles__oxprice = new oxField($orderitem->price);
                    $oOrderArticle->oxorderarticles__oxbprice = new oxField($orderitem->bruttoPrice);
                    $oOrderArticle->oxorderarticles__oxbrutprice = new oxField($orderitem->totalBrutto);
                    $oOrderArticle->oxorderarticles__oxnprice = new oxField($orderitem->nettoPrice);
                    $oOrderArticle->oxorderarticles__oxnetprice = new oxField($orderitem->totalNetto);
                    $oOrderArticle->oxorderarticles__oxvatprice = new oxField($orderitem->totalBrutto - $orderitem->totalNetto);
                    $oOrderArticle->oxorderarticles__oxpersparam = new oxField($orderitem->productParameter);
                    if ($orderitem->stornoPositionSum) {
                        $oOrderArticle->oxorderarticles__oxprice = new oxField($oOrderArticle->oxorderarticles__oxprice->value - $orderitem->stornoPositionSum / $oOrderArticle->oxorderarticles__oxamount->value);
                        $oOrderArticle->oxorderarticles__oxbprice = new oxField($oOrderArticle->oxorderarticles__oxbprice->value - $orderitem->stornoPositionSum / $oOrderArticle->oxorderarticles__oxamount->value);
                        $oOrderArticle->oxorderarticles__oxbrutprice = new oxField($oOrderArticle->oxorderarticles__oxbrutprice->value - $orderitem->stornoPositionSum);
                    }
                    $oOrderArticle->oxorderarticles__oxvat = new oxField($orderitem->vat);
                    if ($orderitem->product) {
                        $artid = $oDb->getOne("select oxid from oxarticles where wwforeignid=" . $oDb->quote($orderitem->product->id));
                        if (!$artid)
                            $artid = $oDb->getOne("select oxid from oxarticles where oxid=" . $oDb->quote($orderitem->product->foreignId));
                        if ($artid)
                            $oOrderArticle->oxorderarticles__oxartid = new oxField($artid);
                    }
                    $oOrderArticle->setId(str_replace("-", "", $id));
                    $oOrderArticle->save();
                    $orderitemsids[$id] = true;
                }
            }
            foreach ($orderitemsids as $oxorderartid => $val) {
                if (!$val) {
                    $oDb->execute("delete from oxorderarticles where oxid=" . $oDb->quote($oxorderartid));
                }
            }
            $this->moduleManager->dispatchEvent(WAWIConnectorEvents::AFTER_UPDATE_ORDER, array($data, $oOrder));
            //$oOrder->recalculateOrder();
        }
    }

    public function send_download_links($foreignId, $isPaid, $email)
    {
        $oOrder = oxNew("oxOrder");
        if ($oOrder->load($foreignId)) {
            $oEmail = oxNew("oxemail");
            if (method_exists($oEmail, "sendDownloadLinksMail")) {
                if ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00' && $isPaid)
                    $oOrder->oxorder__oxpaid = new oxField(date('Y-m-d H:i:s'));
                if ($email)
                    $oOrder->oxorder__oxbillemail = new oxField($email);
                $oEmail->sendDownloadLinksMail($oOrder);
            }
        }
    }

    public function get_order_statuses($page = false)
    {
        if (!is_numeric($page))
            return array('count' => 1, 'itemsPerPage' => $this->_itemsPerPage);
        $myConfig = agConfig::getInstance();
        $folders = $myConfig->getConfigParam('aOrderfolder');
        $result = array();
        foreach ($folders as $key => $value) {
            $item = array();
            $item["title"] = agLang::getInstance()->translateString($key, 0, true);
            $item["value"] = $key;
            $item["color"] = $value;
            $result[] = $item;
        }
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_products()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxarticles");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_categories()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxcategories");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_attributes()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxattribute");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_manufacturers()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxmanufacturers");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_productoptions()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxselectlist");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_voucherseries()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxvoucherseries");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_all_discounts()
    {
        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(ADODB_FETCH_ASSOC);
        $result = $oDb->getAll("select oxid as id, wwforeignid as foreignId from oxdiscount");
        $this->utf8_encode_deep($result);
        return $result;
    }

    public function get_metadescription($type, $wwforeignid, $oxid)
    {
        switch ($type) {
            case "product":
                $table = "oxarticles";
                break;
            case "category":
                $table = "oxcategories";
                break;
        }
        if (!$table)
            return;
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        $shopId = $conf->getShopId();
        if ($oxid)
            $oxid = $oDb->getOne("select oxid from $table where oxid=" . $oDb->quote($oxid));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from $table where wwforeignid=" . $oDb->quote($wwforeignid));
        if ($oxid) {
            return $oDb->getOne("select oxdescription from oxobject2seodata where oxobjectid=" . $oDb->quote($oxid) . " and oxshopid=" . $oDb->quote($shopId) . " and oxlang=0");
        }
    }

    public function set_metadescription($type, $wwforeignid, $oxid, $lang, $desc)
    {

        switch ($type) {
            case "product":
                $table = "oxarticles";
                break;
            case "category":
                $table = "oxcategories";
                break;
        }
        if (!$table)
            return;
        $oDb = oxDb::getDb();
        $conf = agConfig::getInstance();
        $shopId = $conf->getShopId();
        if ($oxid)
            $oxid = $oDb->getOne("select oxid from $table where oxid=" . $oDb->quote($oxid));
        if (!$oxid)
            $oxid = $oDb->getOne("select oxid from $table where wwforeignid=" . $oDb->quote($wwforeignid));
        if ($oxid) {

            $seoid = $oDb->getOne("select oxobjectid from oxobject2seodata where oxobjectid=" . $oDb->quote($oxid) . " and oxshopid=" . $oDb->quote($shopId) . " and oxlang=" . intval($lang));
            if (!$seoid) {
                if ($lang !== 0) {
                    $descInMainLang = $oDb->getOne("select oxdescription from oxobject2seodata where oxobjectid=" . $oDb->quote($oxid) . " and oxshopid=" . $oDb->quote($shopId) . " and oxlang=0");
                    if ($desc == $descInMainLang)
                        return;
                }
                $oDb->execute("insert into oxobject2seodata (oxobjectid, oxshopid, oxlang, oxdescription) values (" . $oDb->quote($oxid) . "," . $oDb->quote($shopId) . "," . intval($lang) . "," . $oDb->quote($desc) . ")");
            } else
                $oDb->execute("update oxobject2seodata set oxdescription=" . $oDb->quote($desc) . " where oxobjectid=" . $oDb->quote($oxid) . " and oxshopid=" . $oDb->quote($shopId) . " and oxlang=" . intval($lang));
        }
    }

    public function get_product_stock($foreignId, $id)
    {
        $oArticle = oxNew("oxArticle");

        $oDb = oxDb::getDb();
        if (!$oArticle->load($id)) {
            $id = $oDb->getOne("select oxid from " . $oArticle->getViewName() . " where wwforeignid=" . $oDb->quote($foreignId));
            if ($id) {
                $oArticle->load($id);
            } else
                return "";
        }
        return $oArticle->oxarticles__oxstock->value;
    }

    public function get_seo_url($type, $foreignId, $id)
    {
        switch ($type) {
            case "product":
                $class = "oxarticle";
                break;
            case "category":
                $class = "oxcategory";
                break;
        }
        if (!$class)
            return "";
        $oEntity = oxNew($class);

        $oDb = oxDb::getDb();
        if (!$oEntity->load($id)) {

            $id = $oDb->getOne("select oxid from " . $oEntity->getViewName() . " where wwforeignid=" . $oDb->quote($foreignId));
            if ($id) {
                $oEntity->load($id);

            } else
                return "";
        }
        $url = $oEntity->getLink();
        $url = preg_replace("/\?force_sid=[a-z0-9]+/", "", $url);
        return $url;
    }

    public function get_additional_fields($table, $items, $oEntity = null)
    {
        return $this->moduleManager->get_additional_fields($table, $items, $oEntity);
    }

    public function get_additional_field_names($table)
    {
        return $this->moduleManager->get_additional_field_names($table);
    }

    public function get_filter_sql($table)
    {
        return $this->moduleManager->get_filter_sql($table);
    }

    protected function _pushVirtualOrderItem(&$order, $sCostKey, $sVatKey, $sLabel)
    {
        if ($order[$sCostKey] != 0) {
            $order['items'][] = array(
                'id' => $sCostKey . '-' . $order['id'],
                'productTitle' => $sLabel,
                'quantity' => 1,
                'vat' => $order[$sVatKey],
                'price' => $order[$sCostKey]
            );
        }
        unset($order[$sVatKey]);
        unset($order[$sCostKey]);
    }

    public function toRemoteSqlKeys($aFieldMap, $sOxidPrefix = null, $multiLangFields = null)
    {
        $aFields = array();
        $langid = agConfig::getInstance()->getConfigParam('sDefaultLang');

        foreach ($aFieldMap as $sKey => $sValue) {
            if (intval($langid) > 0 && $multiLangFields && $multiLangFields[$sKey]) {
                if (stristr($sKey, ',')) {
                    $arr = explode(',', $sKey);
                    for ($i = 0; $i < count($arr); $i++)
                        $arr[$i] .= '_' . $langid;
                    $sKey = implode(',', $arr);
                } else
                    $sKey .= "_" . $langid;
            }
            $sUseKey = $sKey;
            if ($sKey == 'oxid' && $sOxidPrefix) {
                $sUseKey = $sOxidPrefix . '.' . $sKey;
            } elseif (stristr($sKey, ',')) {
                $sUseKey = 'CONCAT_WS(' . "' '" . ',' . $sKey . ')';
            }

            $aFields[] = $sUseKey . ' as ' . $sValue;
        }
        return implode(',', $aFields);
    }

    public function toLocalSqlKeys($aFieldMap)
    {
        $aFields = array();
        foreach ($aFieldMap as $sKey => $sValue) {
            $aFields[] = $sValue . ' as ' . $sKey;
        }
        return implode(',', $aFields);
    }

    public function getRecords($oDb, $sQ, $utf8_convert = true)
    {
        $rows = $oDb->getAll($sQ);
        if ($utf8_convert)
            $this->utf8_encode_deep($rows);
        return $rows;
    }

    public function _transformRecordSet($rs, $utf8_convert = true)
    {
        $result = array();
        if ($rs) {
            while (!$rs->EOF) {
                $result[] = $rs->fields;
                $rs->MoveNext();
            }
        } else {
            die(oxDb::getDb()->ErrorMsg());
        }
        if ($utf8_convert)
            $this->utf8_encode_deep($result);
        return $result;
    }

    public function _prepareForUtf8($input)
    {
        if (!$input)
            return $input;
        $conf = agConfig::getInstance();
        return $input;
    }

    protected function utf8_encode_deep(&$input)
    {
        if (is_string($input)) {

        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->utf8_encode_deep($value);
            }

            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));

            foreach ($vars as $var) {
                $this->utf8_encode_deep($input->$var);
            }
        }
    }

    protected function utf8_decode_deep(&$input)
    {
        if (is_string($input)) {
        } else if (is_array($input)) {
            foreach ($input as &$value) {
                $this->utf8_decode_deep($value);
            }

            unset($value);
        } else if (is_object($input)) {
            $vars = array_keys(get_object_vars($input));

            foreach ($vars as $var) {
                $this->utf8_decode_deep($input->$var);
            }
        }
    }

    protected function updateSEO($objectId)
    {
        $oDb = oxDb::getDb();
        $oDb->execute("update oxseo set oxexpired=1 where oxobjectid=" . $oDb->quote($objectId));
    }

    protected $_aLanguageIds;
    protected $_aLanguageArray;

    protected function _getLanguageIds()
    {
        if ($this->_aLanguageIds === null)
            $this->_aLanguageIds = agLang::getInstance()->getLanguageIds();
        return $this->_aLanguageIds;
    }

    protected function _getLanguageArray()
    {
        if ($this->_aLanguageArray === null)
            $this->_aLanguageArray = agLang::getInstance()->getLanguageArray(null, false);
        return $this->_aLanguageArray;
    }

    protected function _getLanguageId($code)
    {
        if ($this->_aLanguageIds === null)
            $this->_aLanguageIds = agLang::getInstance()->getLanguageIds();
        foreach ($this->_aLanguageIds as $key => $val)
            if ($val == $code)
                return $key;
        return null;
    }

    protected function _clearCatCache()
    {
        @unlink(getShopBasePath() . "tmp/oxpec_aLocalCatCache.txt");
        @unlink(getShopBasePath() . "tmp/oxpec_aLocalManufacturerCache.txt");
        @unlink(getShopBasePath() . "tmp/oxc_aLocalCatCache.txt");
        @unlink(getShopBasePath() . "tmp/oxc_aLocalManufacturerCache.txt");
    }

    public function activate_oss()
    {
        $oConf = agConfig::getInstance();
        oxDb::getDb()->execute("delete from oxconfig where oxvarname='wawiuseoss'");
        $oConf->saveShopConfVar("bool", "wawiuseoss", 1, null, "module:aggrowawi");
    }

    public function upload_file($path, $data)
    {
        $data = base64_decode($data);
        if ($data)
            file_put_contents(getShopBasePath() . $path, $data);
    }

    protected function _clearSeoCache()
    {
        $aFiles = glob(getShopBasePath() . "tmp/*seo.txt");
        if (count($aFiles) > 0)
            foreach ($aFiles as $filename) {
                @unlink($filename);
            }

    }

    public function cleartmp()
    {
        $aFiles = glob(getShopBasePath() . "tmp/*.txt");
        if (count($aFiles) > 0) {
            foreach ($aFiles as $filename) {
                if (strpos($filename, ".htaccess") === FALSE)
                    @unlink($filename);
            }
        }
        $aFiles = glob(getShopBasePath() . "tmp/*.php");
        if (count($aFiles) > 0) {
            foreach ($aFiles as $filename) {
                if (strpos($filename, ".htaccess") === FALSE)
                    @unlink($filename);
            }
        }
        $aFiles = glob(getShopBasePath() . "tmp/smarty/*.php");
        if (count($aFiles) > 0) {
            foreach ($aFiles as $filename) {
                @unlink($filename);
            }
        }
        $aFiles = glob(getShopBasePath() . "tmp/" . "([^\.])");
        if (count($aFiles) > 0) {
            foreach ($aFiles as $filename) {
                @unlink($filename);
            }
        }
    }

}

function cmp_by_sort($a, $b)
{
    return $a->sort > $b->sort;
}
