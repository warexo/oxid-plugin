<?php

namespace Warexo\Application\Model;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;

class User extends User_parent
{
    protected $_aSkipSaveFields = array('oxtimestamp');
    protected $wwuserdeleted;
    protected $wwSavedFields = null;

    public function delete($sOXID = NULL)
    {
        if (!$sOXID) {
            $sOXID = $this->getId();
        }
        if (!$sOXID) {
            return false;
        }
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        if ($sOXID)
            $this->load($sOXID);
        $sOXIDQuoted = $oDb->quote($sOXID);
        $database = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $database->startTransaction();
        try {
            $quotedUserId = $database->quote($sOXID);

            $this->wrxdeleteAddresses($database);
            $this->wrxdeleteUserFromGroups($database);
            $this->wrxdeleteBaskets($database);
            $this->wrxdeleteNewsletterSubscriptions($database);
            $this->wrxdeleteDeliveries($database);
            $this->wrxdeleteDiscounts($database);
            $this->wrxdeleteRecommendationLists($database);
            $this->wrxdeleteReviews($database);
            $this->wrxdeleteRatings($database);
            $this->wrxdeletePriceAlarms($database);
            $this->wrxdeleteAcceptedTerms($database);
            $this->wrxdeleteNotOrderRelatedRemarks($database);

            $this->deleteAdditionally($quotedUserId);

            $fields = array(
                    'oxuser__oxcompany',
                    'oxuser__oxfname',
                    'oxuser__oxlname',
                    'oxuser__oxaddinfo',
                    'oxuser__oxstreet',
                    'oxuser__oxstreetnr',
                    'oxuser__oxzip',
                    'oxuser__oxcity',
                    'oxuser__oxcountryid',
                    'oxuser__oxfon',
                    'oxuser__oxfax',
                    'oxuser__oxpassword',
                    'oxuser__oxpasssalt',
                    'oxuser__oxustid',
                    'oxuser__oxactive',
                    'oxuser__oxprivfon',
                    'oxuser__oxurl',
                    'oxuser__oxusername',
                    'oxuser__wwdeleted'
                );
            foreach ($fields as $field)
                $this->wwSavedFields[$field] = $this->{$field}->value;
            $this->oxuser__oxcompany = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxfname = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxlname = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxaddinfo = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxstreet = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxstreetnr = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxzip = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxcity = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxcountryid = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxfon = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxfax = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxpassword = new \OxidEsales\Eshop\Core\Field(uniqid());
            $this->oxuser__oxustid = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxactive = new \OxidEsales\Eshop\Core\Field(0);
            $this->oxuser__oxprivfon = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxurl = new \OxidEsales\Eshop\Core\Field('-');
            $this->oxuser__oxusername = new \OxidEsales\Eshop\Core\Field('~deleted@@'.uniqid());
            $this->oxuser__wwdeleted = new \OxidEsales\Eshop\Core\Field(1);
            $this->save();
            $this->wwuserdeleted = true;
            $database->commitTransaction();
            return true;
        } catch (\Exception $exeption) {
            $database->rollbackTransaction();

            throw $exeption;
        }

        return true;
    }

    protected function _dbLogin(string $userName, $password, $shopId)
    {
        parent::_dbLogin($userName, $password, $shopId);
        if (!$this->oxuser__oxid->value)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $sUserSelect = "oxuser.oxusername = " . $oDb->quote($sUser);
            $sSelect = "select oxid, wwforeignpassword from oxuser where oxuser.oxactive = 1 and {$sUserSelect}";
            $row = $oDb->getRow($sSelect);
            if ($row && @$row[0] && strpos($row[1],"$") === 0)
            {
                if (password_verify($sPassword, $row[1]))
                {
                    $this->load($row[0]);
                }
            }
        }
    }

    public function isSamePassword($sNewPass)
    {
        $blResult = parent::isSamePassword($sNewPass);
        if (!$blResult && $this->oxuser__wwforeignpassword->value && password_verify($sNewPass, $this->oxuser__wwforeignpassword->value))
        {
            $blResult = true;
        }
        return $blResult;
    }

    public function save()
    {
        if ($this->wwuserdeleted)
        {
            $this->setId('');
            foreach ($this->wwSavedFields as $field=>$value)
                $this->{$field} = new \OxidEsales\Eshop\Core\Field($value);
        }
        $res = parent::save();
        if ($this->wwuserdeleted)
        {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $this->oxuser__oxrights = new \OxidEsales\Eshop\Core\Field('user');
            $oDb->execute("update oxuser set oxrights='user' where oxid=".$oDb->quote($this->getId()));
            $this->wwuserdeleted = false;
        }
        return $res;
    }

    /**
     * Deletes User from groups.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteUserFromGroups(DatabaseInterface $database)
    {
        $database->execute('delete from oxobject2group where oxobject2group.oxobjectid = :oxobjectid', [
            ':oxobjectid' => $this->getId()
        ]);
    }

    /**
     * Deletes deliveries.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteDeliveries(DatabaseInterface $database)
    {
        $database->execute('delete from oxobject2delivery where oxobjectid = :oxobjectid', [
            ':oxobjectid' => $this->getId()
        ]);
    }

    /**
     * Deletes discounts.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteDiscounts(DatabaseInterface $database)
    {
        $database->execute('delete from oxobject2discount where oxobjectid = :oxobjectid', [
            ':oxobjectid' => $this->getId()
        ]);
    }

    /**
     * Deletes user accepted terms.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteAcceptedTerms(DatabaseInterface $database)
    {
        $database->execute('delete from oxacceptedterms where oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
    }

    /**
     * Deletes User addresses.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteAddresses(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxaddress WHERE oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\Address::class);
    }

    /**
     * Deletes noticelists, wishlists or saved baskets
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteBaskets(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxuserbaskets WHERE oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\UserBasket::class);
    }

    /**
     * Deletes not Order related remarks.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteNotOrderRelatedRemarks(DatabaseInterface $database)
    {
        $sql = 'SELECT oxid FROM oxremark WHERE oxparentid = :oxparentid and oxtype != :notoxtype';
        $ids = $database->getCol($sql, [
            ':oxparentid' => $this->getId(),
            ':notoxtype' => 'o'
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\Remark::class);
    }

    /**
     * Deletes recommendation lists.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteRecommendationLists(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxrecommlists WHERE oxuserid = :oxuserid ', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\RecommendationList::class);
    }

    /**
     * Deletes newsletter subscriptions.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteNewsletterSubscriptions(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxnewssubscribed WHERE oxuserid = :oxuserid ', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\NewsSubscribed::class);
    }


    /**
     * Deletes User reviews.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteReviews(DatabaseInterface $database)
    {
        $ids = $database->getCol('select oxid from oxreviews where oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\Review::class);
    }

    /**
     * Deletes User ratings.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeleteRatings(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxratings WHERE oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\Rating::class);
    }

    /**
     * Deletes price alarms.
     *
     * @param DatabaseInterface $database
     */
    private function wrxdeletePriceAlarms(DatabaseInterface $database)
    {
        $ids = $database->getCol('SELECT oxid FROM oxpricealarm WHERE oxuserid = :oxuserid', [
            ':oxuserid' => $this->getId()
        ]);
        array_walk($ids, [$this, 'wrxdeleteItemById'], \OxidEsales\Eshop\Application\Model\PriceAlarm::class);
    }

    private function wrxdeleteItemById($id, $key, $className)
    {
        /** @var \OxidEsales\Eshop\Core\Model\BaseModel $modelObject */
        $modelObject = oxNew($className);

        if ($modelObject->load($id)) {
            if ($this->_blMallUsers) {
                $modelObject->setIsDerived(false);
            }
            $modelObject->delete();
        }
    }
}