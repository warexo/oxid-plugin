<?php

namespace Warexo\Application\Model;

class DiscountAccessory extends \OxidEsales\Eshop\Core\Model\BaseModel {
    /**
     * Object core table name
     *
     * @var string
     */
    protected $_sCoreTbl = 'oxdiscountaccessory';

    /**
     * Current class name
     *
     * @var string
     */
    protected $_sClassName = 'oxdiscountaccessory';

    public function __construct($aParams = null)
    {
        parent::__construct();
        $this->init($this->_sCoreTbl);
    }

}