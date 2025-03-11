<?php

namespace Warexo\Core;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;

class SettingsHelper
{
    public static function getBool($moduleId, $name)
    {
        $moduleSettingService = ContainerFacade::get(ModuleSettingServiceInterface::class);
        return $moduleSettingService->getBoolean($name, $moduleId);
    }

    public static function getString($moduleId, $name)
    {
        $moduleSettingService = ContainerFacade::get(ModuleSettingServiceInterface::class);
        return $moduleSettingService->getString($name, $moduleId);
    }

    public static function getArray($moduleId, $name)
    {
        return [];
        $moduleSettingService = ContainerFacade::get(ModuleSettingServiceInterface::class);
        return $moduleSettingService->getCollection($name, $moduleId);
    }
}