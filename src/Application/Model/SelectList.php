<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;

class SelectList extends SelectList_parent
{
    public function getIcons()
    {
        if (!$this->oxselectlist__wwvalicons->value)
            return array();
        $aIcons = array();

        $aSplitIcons = explode('|', $this->oxselectlist__wwvalicons->value );

        foreach ($aSplitIcons as $key => $value)
        {
            if ($value)
                $aIcons[$key] = \OxidEsales\Eshop\Core\Registry::getConfig()->getPictureUrl("master/productoption/icon/" . $value);
            else
                $aIcons[$key] = '';
        }
        return $aIcons;
    }

    public function getSelectionIcon($ind)
    {
        $icons = $this->getIcons();
        return @$icons[$ind];
    }

    public function getOptionDescriptions()
    {
        if (!$this->oxselectlist__wwvaldata->value)
            return array();
        $aDescriptions = explode('|', $this->oxselectlist__wwvaldata->value );
        return $aDescriptions;
    }

    public function getOptionDescription($ind)
    {
        $descriptions = $this->getOptionDescriptions();
        return @$descriptions[$ind];
    }
}