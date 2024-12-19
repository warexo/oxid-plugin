<?php

//ini_set('display_errors','1');
//Modify


class connectors
{

    public static $connector;

}


ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT);
require_once('oxid.php');
if (file_exists(getShopBasePath() . 'agcore'))
    foreach (glob(getShopBasePath() . 'agcore/*.php') as $file)
        include_once($file);
$connector = new OxidConnector();
connectors::$connector = $connector;

//Leave alone
$user = $_REQUEST['user'];
$pass = $_REQUEST['pass'];
set_time_limit(1200);
ini_set('memory_limit', '2000M');

if ($connector->auth($user, $pass)) {
    $method = $_REQUEST['method'];
    $params = json_decode(($_REQUEST['params']));
    if (!is_array($params))
        $params = array();
    if ($method == "callobjectmethod") {
        $objectmethod = $_REQUEST['objectmethod'];
        $object = json_decode($_REQUEST['object']);
        switch ($_REQUEST['entity']) {
            case "product":
                $type = "oxarticle";
                break;
        }
        if ($type) {
            $oEntity = oxNew($type);

            if (!$oEntity->load($object->foreignid)) {
                $oDb = oxDb::getDb();
                $oxid = $oDb->getOne("select oxid from " . $oEntity->getViewName() . " where wwforeignid=" . $oDb->quote($object->id));
                $oEntity->load($oxid);
            }
            $result = call_user_func_array(array($oEntity, $objectmethod), $params);
        }
    } else if (method_exists($connector, $method))
        $result = call_user_func_array(array($connector, $method), $params);
    else
        $result = ModuleManager::getInstance()->call($method, $params);
    header('Content-Type: application/json');
    echo json_encode($result);
}