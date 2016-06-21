<?php

/**
 * This file defines data model for Orders
 *
 * PHP version 5
 * 
 * @category  asign
 * @package   AsignYellowcube_v2.0_CE_4.3
 * @author    entwicklung@a-sign.ch
 * @copyright A-Sign
 * @license   http://www.a-sign.ch/
 * @version   2.0
 * @link      http://www.a-sign.ch/
 * @see       Orders
 * @since     File available since Release 1.0
 */

namespace Shopware\CustomModels\AsignModels;
 
use Shopware\CustomModels\AsignModels\Product;
use Shopware\Components\Model\ModelEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* Defines data model for Orders
* 
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_4.3
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/

/**
 * @ORM\Entity
 * @ORM\Table(name="asign_orders")
 */
class Orders extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer $ordid
     *     
     * @ORM\Column(type="integer")     
     */
    private $ordid;    

    /**
     * @var integer $lastSent
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $lastSent = false;    

    /**
     * @var string $ycReference
     *
     * @ORM\Column()
     */
    private $ycReference;

    /**
     * @var string $ycResponse
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $ycResponse = null;

    /**
     * @var string $ycWabResponse
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $ycWabResponse = null;

    /**
     * @var string $ycWarResponse
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $ycWarResponse = null;
       
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $ordid
     */
    public function setOrdid($ordid)
    {
        $this->ordid = $ordid;
    }

    /**
     * @return int
     */
    public function getOrdid()
    {
        return $this->ordid;
    }

    /**
     * @param int $lastSent
     */
    public function setLastSent($lastSent)
    {
        $this->lastSent = $lastSent;
    }

    /**
     * @return int
     */
    public function getLastSent()
    {
        return $this->lastSent;
    }    

    /**
     * @param string $ycReference
     */
    public function setYcReference($ycReference)
    {
        $this->ycReference = $ycReference;
    }

    /**
     * @return string
     */
    public function getYcReference()
    {
        return $this->ycReference;
    }

    /**
     * @param string $ycresponse
     */
    public function setYcResponse($ycresponse)
    {
        $this->ycResponse = $ycresponse;
    }

    /**
     * @return string
     */
    public function getYcResponse()
    {
        return $this->ycResponse;
    }

    /**
     * @param string $ycwabresponse
     */
    public function setYcWabResponse($ycwabresponse)
    {
        $this->ycWabResponse = $ycwabresponse;
    }

    /**
     * @return string
     */
    public function getYcWabResponse()
    {
        return $this->ycWabResponse;
    }

    /**
     * @param string $ycwarresponse
     */
    public function setYcWarResponse($ycwarresponse)
    {
        $this->ycWarResponse = $ycwarresponse;
    }

    /**
     * @return string
     */
    public function getYcWarResponse()
    {
        return $this->ycWarResponse;
    }

    /** BS Tracking code value set **/
    protected $_bIsTrackingResponse = false;

    /**
     * Returns EORI for userid
     *
     * @param integer $userID user Id
     *
     * @return string
     */
    public static function getOrderEoriNumber($userID)
    {
        $iEori = Shopware()->Db()->fetchOne("select `text1` from `s_user_billingaddress_attributes` where `billingID` = (select `id` from `s_user_billingaddress` where `userID` = '" . $userID . "')");

        return $iEori ? $iEori : "-";
    }
    
    /**
     * Updates Tara, Tariff and Origin details
     *
     * @param array $orderArticles Order articles
     *
     * @return null
     */
    public function updateHandlingInfo($orderArticles)
    {
        $oProduct = new Product();
        foreach ($orderArticles as $article) {         
            $aHandling = $oProduct->getHandlingInfo($article['articleID']);
            $this->updateOrderArticlesHandlingInfo($aHandling, $article['articleID']);   
        }
    }

    /**
     * Update order articles based on artid
     *
     * @param array $aIntHandling array of handling data
     * @param integer $articleID article item id
     *
     * @return null
     */
    public function updateOrderArticlesHandlingInfo($aIntHandling, $articleID)
    {
        if ($articleID > 0) {
            /// sinc it has to be displayed on invoice change to countryname..
            $localeRepo = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->getDefault();
            $localeId= $localeRepo->getLocale()->getId();
            
            // get country name.
            $sColumn = "countryname";
            if ($localeId == 2) {
                $sColumn = "countryen";
            }
            
            $sCountry = Shopware()->Db()->fetchOne("select `" . $sColumn . "` from `s_core_countries` where `id` = '" . $aIntHandling['origin'] . "'");
            
            // update query executed.
            $sOrdArtQuery = "update `s_order_details` set `tariff` = '" . $aIntHandling['tariff'] . "', `tara` = '" . $aIntHandling['tara'] . "', `origin` = '" . $sCountry . "' where `articleID` = '" . $articleID . "'";
            Shopware()->Db()->query($sOrdArtQuery);
        }
    }

    /**
     * Returns order data based on ordid
     *
     * @param integer $ordid    order item id
     * @param boolean $isDirect if direct from CO    
     * @param bool    $artid    if its a CRON
     *
     * @return array
     */
    public function getOrderDetails($ordid, $isDirect = false, $isCron = false)
    {
        // get order details based on query
        $sSql = "SELECT so.id as ordid, so.ordernumber as ordernumber, so.ordertime as ordertime, so.paymentID as paymentid, so.dispatchID as dispatchid, sob.salutation as sal, sob.company, sob.department, CONCAT(sob.firstname, ' ', sob.lastname) as fullname, CONCAT(sob.street, ' ', sob.streetnumber) as streetinfo, sob.zipcode as zip, sob.city as city, scc.countryiso as country, su.email as email, spd.comment as shipping, scl.locale as language";
        $sSql .= " FROM s_order so";
        $sSql .= " JOIN s_order_shippingaddress sob ON so.id = sob.orderID"; 
        $sSql .= " JOIN s_core_countries scc ON scc.id = sob.countryID";
        $sSql .= " JOIN s_user su ON su.id = so.userID";
        $sSql .= " JOIN s_premium_dispatch spd ON so.dispatchID = spd.id";
        $sSql .= " JOIN s_core_locales scl ON so.language = scl.id";
        
        // cron?
        if ($isCron) {
            $sSql .= " JOIN asign_orders aso ON so.id = aso.ordid";
        }

        // if directly from Thank you page        
        if ($isDirect) {
            $sSql .= " WHERE so.ordernumber = '" . $ordid . "'";
        } else {
            $sSql .= " WHERE so.id = '" . $ordid . "'";
        }        

        // cron?
        if ($isCron) {        
            $sSql .= " AND aso.ycReference = 0";
        }

        $aOrders = Shopware()->Db()->fetchRow($sSql);
        $orderId = $aOrders['ordid'];
        
        // get order article details
        $aOrders['orderarticles'] = Shopware()->Db()->fetchAll("SELECT `articleID`, `articleordernumber`, `name`, `quantity`, `ean` FROM `s_order_details` WHERE `orderID` = '" . $orderId . "' AND `articleID` <> 0");

        return $aOrders;
    }

    /**
     * Function to save the Response
     * received from Yellowcube. Modes included:
     * WAB, WAR, DC = Direct Call
     *
     * @param array $aResponseData Array of response     
     * @param string $ordid        Order id     
     * @param string $mode         Mode of transfer
     *
     * @return null
     */
    public function saveResponseData($aResponseData, $ordid, $mode = null)
    {
        // based on mode switch the response
        if ($mode !== null) {
            // if direct then?
            if ($mode === 'DC') {
                $clrResponse = $aResponseData['data'];
                $sColumn = 'ycResponse';                
            } else {
                $clrResponse = $aResponseData;
                if ($mode === 'WAB') {
                    $sColumn = 'ycWabResponse';
                } elseif ($mode === 'WAR') {
                    $sColumn = 'ycWarResponse';                                    
                } 
            }
        } else {
            $clrResponse = $aResponseData['data'];
            $sColumn = 'ycResponse';
        }

        // format as object2array
        $clrResponse = (array)$clrResponse;        
        if (count($clrResponse) > 0) {        
            // if response is not "E" then?
            if ($clrResponse['StatusType'] !== 'E') {
                $sReference = ", `ycReference` = '" . $clrResponse['Reference'] . "'";
            }
        
            // push in db..
            $sData = serialize($clrResponse);
            if ($mode === 'DC') {
                $sWhere = " where `ordernumber` = '" . $ordid . "'";
            } else {
                $sWhere = " where `ordid` = '" . $ordid . "'";
            }
            
            // update reference number, but first check if alreay entry?
            $iCount = Shopware()->Db()->fetchOne("select count(*) from `asign_orders` where `ordid` = '" . $ordid . "'");
            // if present then?        
            if ($iCount) {
                $sQuery = "update `asign_orders` set `" . $sColumn . "` = '" . $sData . "'" . $sReference . $sWhere;
            } else {
                $sQuery = "insert into `asign_orders` set `ordid` = '" . $ordid . "', `lastSent` = 1, `" . $sColumn . "` = '" . $sData ."'" . $sReference;
            }
            Shopware()->Db()->query($sQuery);

            // update tracking code in s_order table
            if ($mode === 'WAR') {
                $sTrackingCode = $aResponseData[WAR]->GoodsIssue->CustomerOrderHeader->PostalShipmentNo;
                Shopware()->Db()->query("update `s_order` set `trackingcode` = '" . $sTrackingCode . "' where `id` = '" . $ordid . "'");

                $this->_bIsTrackingResponse = true;
            }            
        }
    }

    /**
     * Getter method field data based on id
     * @return string
     */
    public function getFieldData($oId, $sField)
    {
        return Shopware()->Db()->fetchOne("select `" . $sField . "` from `asign_orders` where `ordid` = '" . $oId . "'");    
    }

    /**
     * Getter method for the boolean status variable
     * @return bool
     */
    public function isTrackingNrResponse()
    {
        return $this->_bIsTrackingResponse;
    }

    /**
     * Returns saved status from the saved data
     *
     * @param string $itemId Item id
     * @param string $sTable Table name
     *
     * @param string $sColumn
     * @return array
     */
    public function getYellowcubeReport($itemId, $sTable, $sColumn = 'ycResponse')
    {        
        $sQuery = "select `" . $sColumn . "` from `" . $sTable . "` where `ordid` = '" . $itemId ."'";
        $aComplete = Shopware()->Db()->fetchOne($sQuery);
        $aResponse = unserialize($aComplete);
       
        $aReturn = array();
        if (!empty($aResponse)) {
            foreach ($aResponse as $key => $result) {
                $aReturn[$key] = $result;
            }           
        }
        
        return $aReturn;
    }
}
