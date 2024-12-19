<?php

class PaypalModule
{

    public function get_paypal_details($orderid)
    {
        if (!class_exists("agppbankdata")) {
            if (class_exists("\OxidSolutionCatalysts\PayPal\Model\PayPalOrder")) {
                $order = oxNew("oxorder");
                if ($order->load($orderid)) {
                    try {
                        if ($order->getPayPalOrderIdForOxOrderId()) {
                            $paypalOrder = $order->getPayPalCheckoutOrder();
                            $capture = $order->getOrderPaymentCapture();
                            $transactionId = $capture ? $capture->id : '';
                            $container = \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory::getInstance()->getContainer();
                            $orderRepository = $container->get(\OxidSolutionCatalysts\PayPal\Service\OrderRepository::class);
                            $payPalOrderDetails = $orderRepository->paypalOrderByOrderIdAndPayPalId($orderid, $paypalOrder->id, $transactionId);
                            if ($payPalOrderDetails && $payPalOrderDetails->oscpaypal_order__oscpaypalpuiiban->value) {
                                $result = new stdClass();
                                $result->accountHolder = $payPalOrderDetails->oscpaypal_order__oscpaypalpuiaccountholdername->value;
                                $result->accountNumber = $payPalOrderDetails->oscpaypal_order__oscpaypalpuiiban->value;
                                $result->bankCode = $payPalOrderDetails->oscpaypal_order__oscpaypalpuibic->value;
                                $result->bankName = $payPalOrderDetails->oscpaypal_order__oscpaypalpuibankname->value;
                                $result->invoiceReference = $payPalOrderDetails->oscpaypal_order__oscpaypalpuipaymentreference->value;
                                return $result;
                                //$result->duedate = date('d.m.Y', strtotime($oBillData->agppbankdata__oxduedate->value));
                            }
                        }
                    } catch (Exception $ex) {

                    }
                }

            }
            return null;
        }
        $oBillData = oxNew("agppbankdata");
        $result = null;
        if ($oBillData->loadByOrderId($orderid) && $oBillData->agppbankdata__oxduedate->value && $oBillData->agppbankdata__oxduedate->value != '0000-00-00') {
            $result = new stdClass();
            $result->accountHolder = $oBillData->agppbankdata__oxaccountholder->value;
            $result->accountNumber = $oBillData->agppbankdata__oxaccount->value;
            $result->bankCode = $oBillData->agppbankdata__oxbic->value;
            $result->bankName = $oBillData->agppbankdata__oxbankname->value;
            $result->invoiceReference = $oBillData->agppbankdata__oxreference->value;
            $result->duedate = date('d.m.Y', strtotime($oBillData->agppbankdata__oxduedate->value));
        }

        return $result;
    }
}

if (!file_exists(getShopBasePath() . "modules/agpaypal"))
    ModuleManager::getInstance()->registerModule(new PaypalModule);