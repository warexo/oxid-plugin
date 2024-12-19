<?php

foreach (glob(getShopBasePath() . "wawi/*_module.php") as $moduleFile)
    include_once($moduleFile);
foreach (glob(getShopBasePath() . "modules/**") as $moduleDir)
    if (is_dir($moduleDir . "/warexo")) {
        foreach (glob($moduleDir . "/warexo/*_module.php") as $moduleFile)
            include_once($moduleFile);
    } else if (file_exists($moduleDir . "/vendormetadata.php")) {
        foreach (glob($moduleDir . "/**") as $moduleDir2)
            if (is_dir($moduleDir2 . "/warexo")) {
                foreach (glob($moduleDir2 . "/warexo/*_module.php") as $moduleFile)
                    include_once($moduleFile);
            }
    }

class ModuleManager
{

    protected static $instance;
    protected $modules = array();
    protected $eventListeners = array();
    protected $connector;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new ModuleManager();
        }
        return self::$instance;
    }

    public function setConnector($connector)
    {
        $this->connector = $connector;
    }

    public function getConnector()
    {
        return $this->connector;
    }

    public function registerModule($module)
    {
        $this->modules[] = $module;
    }

    public function addEventListener($event, $module, $function)
    {
        if (!is_array($this->eventListeners[$event])) $this->eventListeners[$event] = array();
        $this->eventListeners[$event][] = array('module' => $module, 'function' => $function);
    }

    public function dispatchEvent($event, $params = null)
    {
        if (is_array($this->eventListeners[$event])) {
            foreach ($this->eventListeners[$event] as $listener) {
                call_user_func_array(array($listener['module'], $listener['function']), $params);
            }
        }
    }

    public function get_additional_fields($table, $items, $oEntity = null)
    {
        foreach ($this->modules as $module) {
            if (method_exists($module, "get_additional_fields")) {
                $items = call_user_func_array(array($module, "get_additional_fields"), array($table, $items, $oEntity));
            }
        }
        return $items;
    }

    public function get_additional_field_names($table)
    {
        $arr = array();
        foreach ($this->modules as $module) {
            if (method_exists($module, "get_additional_field_names")) {
                $arr = array_merge($arr, call_user_func_array(array($module, "get_additional_field_names"), array($table)));
            }
        }
        return $arr;
    }

    public function get_filter_sql($table)
    {
        $sqls = array();
        foreach ($this->modules as $module) {
            if (method_exists($module, "get_filter_sql")) {
                $sql = call_user_func_array(array($module, "get_filter_sql"), array($table));
                if ($sql)
                    $sqls[] = $sql;
            }
        }
        if (count($sqls) > 0)
            return implode(" and ", $sqls);
    }

    public function call($method, $parameters)
    {
        $blRes = true;
        foreach ($this->modules as $module) {
            if (method_exists($module, $method)) {
                $res = call_user_func_array(array($module, $method), $parameters);
                if (!$res)
                    $blRes = false;
                else if ($res !== true)
                    return $res;
            }
        }
        return $blRes;
    }
}