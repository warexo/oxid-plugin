<?php

namespace Warexo\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\DatabaseProvider;

class WarexoConnectorInstaller
{
    public static function onActivate(){
        $myConfig = Registry::getConfig();
        if (!file_exists(getShopBasePath().'wawi'))
            mkdir(getShopBasePath().'wawi');
        foreach (glob(getShopBasePath().'../vendor/aggrosoft/oxid-warexo/wawi/*') as $file)
            copy($file, getShopBasePath().'wawi/'.basename($file));
        if (!file_exists(getShopBasePath().'agcore'))
            mkdir(getShopBasePath().'agcore');
        foreach (glob(getShopBasePath().'../vendor/aggrosoft/oxid-warexo/agcore/*') as $file)
            copy($file, getShopBasePath().'agcore/'.basename($file));
        //execute sql
        self::performsql();

        //update views
        $oMetaData = oxNew('oxDbMetaDataHandler');
        $oMetaData->updateViews();

        //clear tmp
        self::_resetPersistentCache();
    }

    protected static $aSQLs;

    /**
     * Method performs user passed SQL query
     */
    protected static function performsql()
    {
        self::$aSQLs = array();

        if(!file_exists(getShopBasePath().'../vendor/aggrosoft/oxid-warexo/sql/install.sql')) return;
        $sUpdateSQL = file_get_contents(getShopBasePath().'../vendor/aggrosoft/oxid-warexo/sql/install.sql');
        $sUpdateSQL = trim(stripslashes($sUpdateSQL));

        $iLen = strlen($sUpdateSQL);
        if (self::_prepareSQL($sUpdateSQL, $iLen))
        {

            $aQueries = self::$aSQLs;
            $aPassedQueries = array();
            $aQAffectedRows = array();
            $aQErrorMessages = array();
            $aQErrorNumbers = array();

            if (count($aQueries) > 0) {
                $blStop = false;
                $oDB = DatabaseProvider::getDb();
                $iQueriesCounter = 0;
                for ($i = 0; $i < count($aQueries); $i++) {
                    $sUpdateSQL = $aQueries[$i];
                    $sUpdateSQL = trim($sUpdateSQL);
                    if (strlen($sUpdateSQL) > 0) {
                        $aPassedQueries[$iQueriesCounter] = nl2br(htmlentities($sUpdateSQL));
                        if (strlen($aPassedQueries[$iQueriesCounter]) > 200) {
                            $aPassedQueries[$iQueriesCounter] = substr($aPassedQueries[$iQueriesCounter], 0, 200) . "...";
                        }

                        while ($sUpdateSQL[strlen($sUpdateSQL) - 1] == ";") {
                            $sUpdateSQL = substr($sUpdateSQL, 0, (strlen($sUpdateSQL) - 1));
                        }

                        try {
                            $oDB->execute($sUpdateSQL);
                        } catch (\Exception $oExcp) {
                            // catching exception ...
                            $blStop = true;
                        }

                        $aQAffectedRows [$iQueriesCounter] = null;
                        $aQErrorMessages[$iQueriesCounter] = null;
                        $aQErrorNumbers [$iQueriesCounter] = null;

                        $iQueriesCounter++;

                    }
                }
            }
        }
    }


    /**
     * Method parses givent SQL queries string and returns array on success
     *
     * @param string  $sSQL    SQL queries
     * @param integer $iSQLlen query lenght
     *
     * @return mixed
     */
    protected static function _prepareSQL($sSQL, $iSQLlen)
    {
        $sChar = "";
        $sStrStart = "";
        $blString = false;

        //removing "mysqldump" application comments
        while (preg_match("/^\-\-.*\n/", $sSQL)) {
            $sSQL = trim(preg_replace("/^\-\-.*\n/", "", $sSQL));
        }
        while (preg_match("/\n\-\-.*\n/", $sSQL)) {
            $sSQL = trim(preg_replace("/\n\-\-.*\n/", "\n", $sSQL));
        }

        for ($iPos = 0; $iPos < $iSQLlen; ++$iPos) {
            $sChar = $sSQL[$iPos];
            if ($blString) {
                while (true) {
                    $iPos = strpos($sSQL, $sStrStart, $iPos);
                    //we are at the end of string ?
                    if (!$iPos) {
                        self::$aSQLs[] = $sSQL;

                        return true;
                    } elseif ($sStrStart == '`' || $sSQL[$iPos - 1] != '\\') {
                        //found some query separators
                        $blString = false;
                        $sStrStart = "";
                        break;
                    } else {
                        $iNext = 2;
                        $blBackslash = false;
                        while ($iPos - $iNext > 0 && $sSQL[$iPos - $iNext] == '\\') {
                            $blBackslash = !$blBackslash;
                            $iNext++;
                        }
                        if ($blBackslash) {
                            $blString = false;
                            $sStrStart = "";
                            break;
                        } else {
                            $iPos++;
                        }
                    }
                }
            } elseif ($sChar == ";") {
                // delimiter found, appending query array
                self::$aSQLs[] = substr($sSQL, 0, $iPos);
                $sSQL = ltrim(substr($sSQL, min($iPos + 1, $iSQLlen)));
                $iSQLlen = strlen($sSQL);
                if ($iSQLlen) {
                    $iPos = -1;
                } else {
                    return true;
                }
            } elseif (($sChar == '"') || ($sChar == '\'') || ($sChar == '`')) {
                $blString = true;
                $sStrStart = $sChar;
            } elseif ($sChar == "#" || ($sChar == ' ' && $iPos > 1 && $sSQL[$iPos - 2] . $sSQL[$iPos - 1] == '--')) {
                // removing # commented query code
                $iCommStart = (($sSQL[$iPos] == "#") ? $iPos : $iPos - 2);
                $iCommEnd = (strpos(' ' . $sSQL, "\012", $iPos + 2))
                    ? strpos(' ' . $sSQL, "\012", $iPos + 2)
                    : strpos(' ' . $sSQL, "\015", $iPos + 2);
                if (!$iCommEnd) {
                    if ($iCommStart > 0) {
                        self::$aSQLs[] = trim(substr($sSQL, 0, $iCommStart));
                    }

                    return true;
                } else {
                    $sSQL = substr($sSQL, 0, $iCommStart) . ltrim(substr($sSQL, $iCommEnd));
                    $iSQLlen = strlen($sSQL);
                    $iPos--;
                }
            } elseif (32358 < 32270 && ($sChar == '!' && $iPos > 1 && $sSQL[$iPos - 2] . $sSQL[$iPos - 1] == '/*')) {
                // removing comments like /**/
                $sSQL[$iPos] = ' ';
            }
        }

        if (!empty($sSQL) && preg_match("/[^[:space:]]+/", $sSQL)) {
            self::$aSQLs[] = $sSQL;
        }

        return true;
    }

    protected static function _resetPersistentCache() {
        $oConfig = Registry::getConfig();
        $oUtils = Registry::getUtils();
        $sCacheDir = $oUtils->getCacheFilePath(null, true);
        $aDir = glob($sCacheDir.'*');
        if(is_array($aDir)) {
            $aDir = preg_grep("/c_fieldnames_|c_tbdsc_|_allfields_/", $aDir);
            foreach($aDir as $iKey => $sData) {
                if(!is_dir($sData)) {
                    @unlink($sData);
                }
            }
        }
    }
}