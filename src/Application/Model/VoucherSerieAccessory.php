<?php

namespace Warexo\Application\Model;

class VoucherSerieAccessory extends \OxidEsales\Eshop\Core\Model\BaseModel {
    /**
     * Object core table name
     *
     * @var string
     */
    protected $_sCoreTbl = 'oxvoucherserieaccessory';

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxvoucherserieaccessory';

    public function __construct($aParams = null)
    {
        parent::__construct();
        $this->init($this->_sCoreTbl);
    }

}