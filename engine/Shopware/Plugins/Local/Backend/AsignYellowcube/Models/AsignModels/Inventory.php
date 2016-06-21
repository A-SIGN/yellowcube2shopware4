<?php

/**
 * This file defines data model for Inventory
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
 * @see       Inventory
 * @since     File available since Release 1.0
 */

namespace Shopware\CustomModels\AsignModels;
 
use Shopware\Components\Model\ModelEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* Defines data model for Inventory
* 
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_4.3
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
 
/**
 * @ORM\Entity
 * @ORM\Table(name="asign_yellowcube")
 */
class Inventory extends ModelEntity
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string $ycarticlenr
     *
     * @ORM\Column(name="ycarticlenr", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $ycarticlenr;

    /**
     * @var string $articlenr
     *
     * @ORM\Column(name="articlenr", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $articlenr;

    /**
     * @var string $artdesc
     *
     * @ORM\Column()
     */
    private $artdesc;

    /**
     * @var string $additional
     *
     * @ORM\Column(name="additional", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $additional;

    /**
     * @var \DateTime $createdon
     *
     * @ORM\Column(name="createdon", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdon;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ycarticlenr
     *
     * @param string $sValue
     * @return Inventory
     */
    public function setYcartnum($sValue)
    {
        if (!empty($sValue)) {
            $this->ycarticlenr = $sValue;
        }
        return $this;
    }

    /**
     * Get Ycarticlenr
     *
     * @return string
     */
    public function getYcartnum()
    {
        return $this->ycarticlenr;
    }

    /**
     * Set articlenr
     *
     * @param string $sValue
     * @return Inventory
     */
    public function setArtnum($sValue)
    {
        if (!empty($sValue)) {
            $this->articlenr = $sValue;
        }
        return $this;
    }

    /**
     * Get Article Number
     *
     * @return string
     */
    public function getArtnum()
    {
        return $this->articlenr;
    }

    /**
     * Set article desc
     *
     * @param string $sValue
     * @return Inventory
     */
    public function setArtDesc($sValue)
    {
        if (!empty($sValue)) {
            $this->artdesc = $sValue;
        }
        return $this;
    }

    /**
     * Get Article Description
     *
     * @return string
     */
    public function getArtDesc()
    {
        return $this->artdesc;
    }

    /**
     * Set additional information
     *
     * @param string $sValue
     * @return Inventory
     */
    public function setAddInfo($sValue)
    {
        if (!empty($sValue)) {
            $this->additional = $sValue;
        }
        return $this;
    }

    /**
     * Get Additional Information
     *
     * @return text
     */
    public function getAddInfo()
    {
        return $this->additional;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Inventory
     */
    public function setCreated($created)
    {
        if (!empty($created)) {
            $this->createdon = $created;
        }
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->createdon;
    }

    /**
     * Stores inventory information received from Yellowcube
     *
     * @param array $aResponseData Array of response
     *
     * @return null
     */
    public function saveInventoryData($aResponseData)
    {   
        // format the response data
        $iCount = 0;

        // reset the inventory data
        $this->resetInventoryData();

        foreach ($aResponseData->ArticleList->Article as $article) {
            $qtyISO  = $article->QuantityUOM->QuantityISO; 
            $qtyUOM  = $article->QuantityUOM->_;                        
            $ycartnr = $article->YCArticleNo;
            $artnr   = $article->ArticleNo;
            $artdesc = $article->ArticleDescription;            

            // entry id to avoid duplicates
            $mainId = substr($ycartnr, 4);

            // frame the additioanal information array
            $aAddInfo = array(
                'EAN'               => $article->EAN,  
                'Plant'             => $article->Plant,
                'StorageLocation'   => $article->StorageLocation,
                'StockType'         => $article->StockType,
                'QuantityISO'       => $qtyISO,
                'QuantityUOM'       => $qtyUOM,
                'YCLot'             => $article->YCLot,
                'Lot'               => $article->Lot,
                'BestBeforeDate'    => $article->BestBeforeDate,
            );
            // serialize the data
            $sAdditional = serialize($aAddInfo);            

            // push in db
            $query = "insert into `asign_yellowcube` set `id` = '" . $mainId . "', `ycarticlenr` = '".$ycartnr."', `articlenr` = '".$artnr."', `artdesc` = '" . $artdesc . "', `additional` = '" . $sAdditional . "' on duplicate key update `createdon` = CURRENT_TIMESTAMP";
            Shopware()->Db()->query($query);             
            
            //update the stock
            $iStock = (int) $qtyUOM;
            Shopware()->Db()->query("update `s_articles_details` set `instock` = '" . $iStock . "' where `ordernumber` = '" . $artnr . "'"); 

            $iCount = $iCount + 1;
        }

        return $iCount;
    }

    /**
     * Resets the oxstock value for all articles that are entered in the YC warehouse.
     * This should be run before setting stock, because YC only sends information on articles, that have
     * over 0 stock.
     */
    public function resetInventoryData()
    {
        $aArticles = Shopware()->Db()->fetchAll("select `artid`, `ycResponse` from `asign_product` where `ycResponse` != ''");

        foreach ($aArticles as $article) {
            $aResponse = unserialize($article['ycResponse']);

            if ($aResponse['StatusCode'] == 100) {
                Shopware()->Db()->query("update `s_articles_details` set `instock` = '0' where `articleID` = '" . $article['artid'] . "'");
            }
        }
    }
}
