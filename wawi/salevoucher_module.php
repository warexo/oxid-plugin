<?php

class VoucherModule
{
    public function get_additional_fields($table, $items, $oEntity = null)
    {
        if ($table == "oxorder") {
            $order = $items;
            $oDb = oxDb::getDb();
            try {
                $order["saleVoucherDiscount"] = $oDb->getOne("select aggiftedvoucherdiscount from oxorder where oxid=" . $oDb->quote($order["id"]));
                $voucherseries = $oDb->getAll("select oxid from oxvoucherseries where agsaleorderid=" . $oDb->quote($order["id"]));
                if (is_array($voucherseries) && count($voucherseries) > 0) {
                    $voucherseries_ids = array();
                    $vouchers_ids = array();
                    foreach ($voucherseries as $item) {
                        $voucherseries_ids[] = $item[0];
                        $vouchers = $oDb->getAll("select oxid from oxvouchers where oxvoucherserieid=" . $oDb->quote($item[0]));
                        foreach ($vouchers as $item2) {
                            $vouchers_ids[] = $item2[0];
                        }
                    }
                    if (count($voucherseries_ids) > 0)
                        $order["salevoucherseries"] = ModuleManager::getInstance()->getConnector()->get_voucher_series(0, null, $voucherseries_ids);
                    if (count($vouchers_ids) > 0)
                        $order["salevouchers"] = ModuleManager::getInstance()->getConnector()->get_vouchers(0, null, $vouchers_ids);
                }
            } catch (Exception $ex) {

            }
            return $order;
        }
        return $items;
    }

    public function get_additional_field_names($table)
    {
        if ($table == "oxvoucher") {
            return array("agisvoucher" => "saleVoucher", "agrestvalue" => "restValue", "agsaleorderid" => "saleOrderId", "agsaleorderarticleid" => "saleOfferItemId");
        } else if ($table == "oxorderarticle") {
            return array("agisvoucher > 0 as saleVoucher");
        } else if ($table == "oxarticle") {
            return array("agisvoucher" => "saleVoucher");
        }
        return array();
    }
}

ModuleManager::getInstance()->registerModule(new VoucherModule);

