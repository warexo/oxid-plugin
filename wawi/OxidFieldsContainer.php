<?php

if (!file_exists('OxidFieldsContainerAdditional.php')) {
    $sClassBase = <<<FIELDS
<?php
    class OxidFieldsContainerAdditional {        
        protected \$_aOtherSavedProductFields = array();
        protected \$_aOtherProductFields = array();
        protected \$_sOtherOfferItemFields = '';
        protected \$_aOtherCategoryFields = array();
        protected \$_aOtherOrderFields = array();
        protected \$_aOtherManufacturerFields = array();
        protected \$_aOtherCustomerFields = array();
        protected \$_aOtherAttributeFields = array();
        protected \$_aOtherOptionFields = array();
    }
?>
FIELDS;

    file_put_contents('OxidFieldsContainerAdditional.php', $sClassBase);
}

require_once('OxidFieldsContainerAdditional.php');


class OxidFieldsContainer extends OxidFieldsContainerAdditional
{

    protected $_aSavedProductFields = array(
        'oxissearch',
        'oxblfixedprice',
        'oxskipdiscounts',
        'oxtemplate',
    );

    protected $_aProductFields = array(
        'oxid' => 'id',
        'oxactive' => 'active',
        'oxactivefrom' => 'activeFrom',
        'oxactiveto' => 'activeTo',
        'oxtitle,oxvarselect' => 'title',
        'oxparentid' => 'parent',
        'oxsort' => 'sort',
        'oxshortdesc' => 'description',
        'oxlongdesc' => 'longdescription',
        'oxartnum' => 'sku',
        'oxprice' => 'price',
        'oxstock' => 'stock',
        'oxsearchkeys' => 'searchkeys',
        'oxean' => 'ean',
        'oxmpn' => 'mpn',
        'oxdistean' => 'distean',
        'oxweight' => 'weight',
        'oxtprice' => 'uvp',
        'oxtags' => 'tags',
        'oxvendorid' => 'vendor',
        'oxmanufacturerid' => 'manufacturer',
        'oxvarname' => 'variantName',
        'oxthumb' => 'thumb',
        'oxicon' => 'icon',
        'oxvat' => 'tax',
        'oxpic1' => 'picture1',
        'oxpic2' => 'picture2',
        'oxpic3' => 'picture3',
        'oxpic4' => 'picture4',
        'oxpic5' => 'picture5',
        'oxpic6' => 'picture6',
        'oxpic7' => 'picture7',
        'oxpic8' => 'picture8',
        'oxpic9' => 'picture9',
        'oxpic10' => 'picture10',
        'oxpic11' => 'picture11',
        'oxpic12' => 'picture12',
        'oxbprice' => 'purchasePrice',
        'oxunitquantity' => 'unitQuantity',
        'oxunitname' => 'unitName',
        'oxexturl' => 'externalLink',
        'oxurldesc' => 'externalLinkText',
        'oxlength' => 'length',
        'oxwidth' => 'width',
        'oxheight' => 'height',
        'oxdelivery' => 'availableOn',
        'oxstockflag' => 'deliveryStatus',
        'oxmindeltime' => 'minDeliveryTime',
        'oxmaxdeltime' => 'maxDeliveryTime',
        'oxdeltimeunit' => 'deliveryTimeUnit',
        //'wwminpurchasetime' => 'minPurchaseTime',
        //'wwmaxpurchasetime' => 'maxPurchaseTime',
        //'wwpurchasetimeunit' => 'purchaseTimeUnit',
        'wwforeignid' => 'foreignId',
        'wwforeignparentid' => 'foreignParentId',
        'oxnonmaterial' => 'nonMaterial',
        'oxcreated' => 'createdTimestamp',
    );
    protected $_aAttributeFields = array(
        'oxid' => 'id',
        'oxtitle' => 'title',
        'oxpos' => 'sort',
        'oxident' => 'ident',
        'wwforeignid' => 'foreignId',
    );
    protected $_aOptionFields = array(
        'oxid' => 'id',
        'oxtitle' => 'title',
        'oxsort' => 'sort',
        'oxident' => 'ident',
        'oxvaldesc' => 'optionvalues',
        'wwforeignid' => 'foreignId',
    );
    protected $_aObject2OptionFields = array(
        'oxid' => 'id',
        'oxobjectid' => 'product_id',
        'oxselnid' => 'option_id',
    );
    protected $_aCategoryFields = array(
        'oxid' => 'id',
        'oxactive' => 'active',
        'oxparentid' => 'parent',
        'oxtitle' => 'title',
        'oxsort' => 'sort',
        'oxdesc' => 'description',
        'oxlongdesc' => 'longdescription',
        'oxthumb' => 'picture',
        'oxicon' => 'icon',
        'oxhidden' => 'hidden',
        'oxvat' => 'tax',
        'oxextlink' => 'externalLink',
        'wwforeignid' => 'foreignId',
        'wwforeignparentid' => 'foreignParentId'
    );
    protected $_aAddressFields = array(
        'oxid' => 'id',
        'oxfname' => 'firstName',
        'oxlname' => 'lastName',
        'oxstreet,oxstreetnr' => 'street',
        'oxcity' => 'city',
        'oxzip' => 'zip',
        'oxcountryid' => 'country',
        'oxstateid' => 'state',
        'oxfax' => 'fax',
        'oxsal' => 'salutation',
        'oxaddinfo' => 'additionalInfo',
        'oxcompany' => 'company',
        'oxfon' => 'phone',
    );
    protected $_aCustomerFields = array(
        'oxid' => 'id',
        'oxusername' => 'email',
        'oxcustnr' => 'customerNumber',
        'oxfname' => 'firstName',
        'oxlname' => 'lastName',
        'oxstreet,oxstreetnr' => 'street',
        'oxcity' => 'city',
        'oxzip' => 'zip',
        'oxustid' => 'vatId',
        'oxfax' => 'fax',
        'oxsal' => 'salutation',
        'oxbirthdate' => 'birthDate',
        'oxcountryid' => 'country',
        'oxstateid' => 'state',
        'oxfon' => 'phone',
        'oxmobfon' => 'mobilePhone',
        'oxprivfon' => 'privatePhone',
        'oxboni' => 'boni',
        'oxaddinfo' => 'additionalInfo',
        'oxcompany' => 'company',
        'oxurl' => 'url',
        'wwforeignpassword' => 'foreignPassword',
        'wwforeignid' => 'foreignId',
        'wwdeleted' => 'deleted'
    );
    protected $_aCustomerGroupFields = array(
        'oxid' => 'id',
        'oxtitle' => 'title',
        'oxactive' => 'active',
        'wwforeignid' => 'foreignId',
        'wwdisplayproductsforother' => 'displayProductsForOther',
        'wwnettomode' => 'nettoMode'
    );
    protected $_aVendorFields = array(
        'oxid' => 'id',
        'oxactive' => 'active',
        'oxtitle' => 'title',
        'oxshortdesc' => 'description',
        'oxicon' => 'icon',
        'wwforeignid' => 'foreignId'
    );
    protected $_aManufacturerFields = array(
        'oxid' => 'id',
        'oxactive' => 'active',
        'oxtitle' => 'title',
        'oxshortdesc' => 'description',
        'oxicon' => 'icon',
        'wwforeignid' => 'foreignId',
        'wwcompany' => 'company',
        'wwaddress' => 'address',
        'wwemail' => 'email',
        'wwurl' => 'url',
        'wwimportercompany' => 'importerCompany',
        'wwimporteraddress' => 'importerAddress',
        'wwimporteremail' => 'importerEmail',
        'wwimporterurl' => 'importerUrl',
        'wwresponsibleperson' => 'responsiblePerson',
        'wwresponsiblepersonaddress' => 'responsiblePersonAddress',
        'wwresponsiblepersonemail' => 'responsiblePersonEmail',
        'wwresponsiblepersonurl' => 'responsiblePersonUrl',
        'wwcustomgpsrtextforlongdescription' => 'customGPSRTextForLongdescription'
    );
    protected $_aShippingFields = array(
        'oxid' => 'shipping',
        'oxtitle' => 'shippingName',
    );
    protected $_aOrderFields = array(
        'oxid' => 'id',
        'oxorderdate' => 'created',
        'oxtimestamp' => 'updated',
        'oxbillsal' => 'billingSalutation',
        'oxbillemail' => 'billingEMail',
        'oxbillcompany' => 'billingCompany',
        'oxbillfname' => 'billingFirstName',
        'oxbilllname' => 'billingLastName',
        'oxbillstreet, oxbillstreetnr' => 'billingStreet',
        'oxbillustid' => 'billingVatId',
        'oxbillcity' => 'billingCity',
        'oxbillzip' => 'billingZip',
        'oxbillcountryid' => 'billingCountry',
        'oxbillstateid' => 'billingState',
        'oxbillfon' => 'billingPhone',
        'oxbillfax' => 'billingFax',
        'oxdelsal' => 'shippingSalutation',
        'oxdelcompany' => 'shippingCompany',
        'oxdelfname' => 'shippingFirstName',
        'oxdellname' => 'shippingLastName',
        'oxdelstreet, oxdelstreetnr' => 'shippingStreet',
        'oxdelcity' => 'shippingCity',
        'oxdelzip' => 'shippingZip',
        'oxdelcountryid' => 'shippingCountry',
        'oxdelstateid' => 'shippingState',
        'oxdelfon' => 'shippingPhone',
        'oxdelfax' => 'shippingFax',
        'oxuserid' => 'customerId',
        'oxordernr' => 'offerNumber',
        'oxdelcost' => 'deliveryCost',
        'oxdelvat' => 'deliveryVat',
        'oxpaycost' => 'paymentCost',
        'oxpayvat' => 'paymentVat',
        'oxwrapcost' => 'wrapCost',
        'oxwrapvat' => 'wrapVat',
        'oxgiftcardcost' => 'giftCardCost',
        'oxgiftcardvat' => 'giftCardVat',
        'oxdiscount' => 'discount',
        'oxfolder' => 'status',
        'oxpaymenttype' => 'paymentId',
        'oxtransid' => 'paymentTransactionId',
        'oxxid' => 'paymentTransactionId2',
        'oxremark' => 'note',
        'oxcurrency' => 'currency',
        'oxvoucherdiscount' => 'voucherDiscount',
        'oxsenddate' => 'shippingDate',
        'oxpaid' => 'paidDate',
        'oxtsprotectcosts' => 'tsProtectCost',
        'oxtransstatus' => 'transactionStatus',
        'oxstorno' => 'storno',
        'oxdeltype' => 'shipping',
        'wwforeignid' => 'foreignId',
        'oxtrackcode' => 'trackingCode',
        'oxbillnr' => 'invoiceNumber',
        'oxbilldate' => 'invoiceDate',
        'oxlang' => 'language',
        'oxbilladdinfo' => 'billingAdditionalInfo',
        'oxdeladdinfo' => 'shippingAdditionalInfo',
        'oxisnettomode' => 'nettoMode',
        'oxcardtext' => 'giftCardText',
        'oxcardid' => 'giftCardType',
        'wwuseragent' => 'userAgent',
        'wwagentparameter' => 'agentParameter'
    );
    protected $_aVoucherSerieFields = array(
        'oxid' => 'id',
        'oxtimestamp' => 'updated',
        'oxserienr' => 'title',
        'oxseriedescription' => 'description',
        'oxbegindate' => 'beginDate',
        'oxenddate' => 'endDate',
        'oxdiscount' => 'discount',
        'oxdiscounttype' => 'discountType',
        'oxminimumvalue' => 'minimumValue',
        'oxallowsameseries' => 'allowSameSeries',
        'oxallowotherseries' => 'allowOtherSeries',
        'oxallowuseanother' => 'allowUseAnother',
        'oxcalculateonce' => 'calculateOnce',
        'wwforeignid' => 'foreignId'
    );
    protected $_aVoucherSerie2CategoryFields = array(
        'oxid' => 'id',
        'oxdiscountid' => 'voucherserie_id',
        'oxobjectid' => 'category_id',
    );
    protected $_aVoucherSerie2ProductFields = array(
        'oxid' => 'id',
        'oxdiscountid' => 'voucherserie_id',
        'oxobjectid' => 'product_id',
    );
    protected $_aVoucherFields = array(
        'oxid' => 'id',
        'oxtimestamp' => 'updated',
        'oxvouchernr' => 'voucherNumber',
        'oxdateused' => 'usedDate',
        'oxuserid' => 'customerId',
        'oxorderid' => 'orderId',
        'oxvoucherserieid' => 'voucherserieId',
        'oxdiscount' => 'discount',
        'wwforeignid' => 'foreignId',
        'agisvoucher' => 'saleVoucher',
        "agrestvalue" => "restValue",
        "agsaleorderid" => "saleOrderId",
        "agsaleorderarticleid" => "saleOfferItemId"
    );
    protected $_aDiscount2CategoryFields = array(
        'oxid' => 'id',
        'oxdiscountid' => 'discount_id',
        'oxobjectid' => 'category_id',
    );
    protected $_aDiscount2ProductFields = array(
        'oxid' => 'id',
        'oxdiscountid' => 'discount_id',
        'oxobjectid' => 'product_id',
    );
    protected $_aDiscount2CustomerFields = array(
        'oxid' => 'id',
        'oxdiscountid' => 'discount_id',
        'oxobjectid' => 'customer_id',
    );
    protected $_aDiscount2CustomerGroupFields = array(
        'oxobjectid' => 'id',
    );
    protected $_aDiscount2CountryFields = array(
        'oxobjectid' => 'id',
    );
    protected $_aDiscountFields = array(
        'oxid' => 'id',
        'oxactive' => 'active',
        'oxtitle' => 'title',
        'oxactivefrom' => 'beginDate',
        'oxactiveto' => 'endDate',
        'oxamount' => 'minimumPurchaseAmount',
        'oxamountto' => 'maximumPurchaseAmount',
        'oxprice' => 'minimumPurchaseValue',
        'oxpriceto' => 'maximumPurchaseValue',
        'oxaddsum' => 'discount',
        'oxaddsumtype' => 'discountType',
        'wwforeignid' => 'foreignId'
    );
    protected $_aPaymentMap = array(
        'oxidpayadvance' => 'prepayment',
        'oxidpaypal' => 'paypal',
        'oxidcashondel' => 'cashondelivery',
        'susofortueberweisung' => 'sofortueberweisung',
        'billpay_rec' => 'billpayinvoice',
        'billpay_elv' => 'billpaydebit',
        'oxidinvoice' => 'invoice',
        'oxidcreditcard' => 'creditcard',
        'mo_billsafe' => 'billsafe'
    );
    protected $_aPaymentFieldMap = array(
        'lsktonr' => 'iban',
        'lsblz' => 'bic',
        'lsktoinhaber' => 'accountholder',
        'lsbankname' => 'bankname'
    );
    protected $_aObject2CategoryFields = array(
        'oxid' => 'id',
        'oxobjectid' => 'product_id',
        'oxcatnid' => 'category_id',
    );
    protected $_aCustomer2GroupFields = array(
        'oxid' => 'id',
        'oxobjectid' => 'customer_id',
        'oxgroupsid' => 'group_id',
    );
    protected $_sOfferItemFields = '';
    protected $_aProductMultiLangFields = array(
        'oxtitle,oxvarselect' => 'title',
        'oxshortdesc' => 'description',
        'oxlongdesc' => 'longdescription',
        'oxvarname' => 'variantName',
        'oxsearchkeys' => 'searchkeys',
        'oxtags' => 'tags',
    );
    protected $_aAttributeMultiLangFields = array(
        'oxtitle' => 'title',
    );
    protected $_aOptionMultiLangFields = array(
        'oxtitle' => 'title',
        'oxvaldesc' => 'optionvalues',
    );
    protected $_aCategoryMultiLangFields = array(
        'oxtitle' => 'title',
        'oxdesc' => 'description',
        'oxlongdesc' => 'longdescription',
    );
    protected $_aManufacturerMultiLangFields = array(
        'oxtitle' => 'title',
        'oxshortdesc' => 'description'
    );
    protected $_aPaymentMultiLangFields = array(
        'oxdesc' => 'title',
    );
    protected $_aCountryMultiLangFields = array(
        'oxtitle' => 'title',
    );
    protected $_aDiscountMultiLangFields = array(
        'oxtitle' => 'title',
    );
    protected $_iPicsAmount = 12;

    public function __construct()
    {
        if (is_array($this->_aOtherProductFields))
            $this->_aProductFields = array_merge($this->_aProductFields, $this->_aOtherProductFields);
        if (is_array($this->_aOtherCategoryFields))
            $this->_aCategoryFields = array_merge($this->_aCategoryFields, $this->_aOtherCategoryFields);
        if (is_array($this->_aOtherOptionFields))
            $this->_aOptionFields = array_merge($this->_aOptionFields, $this->_aOtherOptionFields);
        if (is_array($this->_aOtherAttributeFields))
            $this->_aAttributeFields = array_merge($this->_aAttributeFields, $this->_aOtherAttributeFields);
        if (is_array($this->_aOtherOrderFields))
            $this->_aOrderFields = array_merge($this->_aOrderFields, $this->_aOtherOrderFields);
        if (is_array($this->_aOtherCustomerFields))
            $this->_aCustomerFields = array_merge($this->_aCustomerFields, $this->_aOtherCustomerFields);
        if (is_array($this->_aOtherAddressFields))
            $this->_aAddressFields = array_merge($this->_aAddressFields, $this->_aOtherAddressFields);
        if (is_array($this->_aOtherDiscountFields))
            $this->_aDiscountFields = array_merge($this->_aDiscountFields, $this->_aOtherDiscountFields);
        if (is_array($this->_aOtherCustomerGroupFields))
            $this->_aCustomerGroupFields = array_merge($this->_aCustomerGroupFields, $this->_aOtherCustomerGroupFields);
        $this->_sOfferItemFields = $this->_sOtherOfferItemFields;
        if (is_array($this->_aOtherManufacturerFields))
            $this->_aManufacturerFields = array_merge($this->_aManufacturerFields, $this->_aOtherManufacturerFields);
        $this->_aProductFields = array_merge($this->_aProductFields, $this->get_additional_field_names("oxarticle"));
        $this->_aCategoryFields = array_merge($this->_aCategoryFields, $this->get_additional_field_names("oxcategory"));
        $this->_aOrderFields = array_merge($this->_aOrderFields, $this->get_additional_field_names("oxorder"));
        $arr = $this->get_additional_field_names("oxorderarticle");
        $str = '';
        $str = '';
        if (count($arr) > 0)
            $str = "," . implode(",", $this->get_additional_field_names("oxorderarticle"));
        $this->_sOfferItemFields = $this->_sOfferItemFields . $str;
        $this->_aCustomerFields = array_merge($this->_aCustomerFields, $this->get_additional_field_names("oxuser"));
        $this->_aAddressFields = array_merge($this->_aAddressFields, $this->get_additional_field_names("oxaddress"));
        $this->_aAttributeFields = array_merge($this->_aAttributeFields, $this->get_additional_field_names("oxattribute"));
        $this->_aManufacturerFields = array_merge($this->_aManufacturerFields, $this->get_additional_field_names("oxmanufacturer"));
        $this->_aOptionFields = array_merge($this->_aOptionFields, $this->get_additional_field_names("oxselectlist"));
        $this->_aVoucherFields = array_merge($this->_aVoucherFields, $this->get_additional_field_names("oxvoucher"));
        $this->_aVoucherSerieFields = array_merge($this->_aVoucherSerieFields, $this->get_additional_field_names("oxvoucherserie"));
        if (is_array($this->_aCustomPaymentMap) && count($this->_aCustomPaymentMap) > 0)
            $this->_aPaymentMap = $this->_aCustomPaymentMap;
        if (is_array($this->_aOtherProductMultiLangFields))
            $this->_aProductMultiLangFields = array_merge($this->_aProductMultiLangFields, $this->_aOtherProductMultiLangFields);
        if (is_array($this->_aOtherCategoryMultiLangFields))
            $this->_aCategoryMultiLangFields = array_merge($this->_aCategoryMultiLangFields, $this->_aOtherCategoryMultiLangFields);
        if ($this->_iCustomPicsAmount)
            $this->_iPicsAmount = $this->_iCustomPicsAmount;
        if (is_array($this->_aOverridedSavedProductFields))
            $this->_aSavedProductFields = $this->_aOverridedSavedProductFields;
        if (is_array($this->_aOtherSavedProductFields))
            $this->_aSavedProductFields = array_merge($this->_aSavedProductFields, $this->_aOtherSavedProductFields);
    }

}