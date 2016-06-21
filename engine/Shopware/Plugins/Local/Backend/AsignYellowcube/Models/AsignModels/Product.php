<?php

/**
 * This file defines data model for Products
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
 * @see       Errorlogs
 * @since     File available since Release 1.0
 */

namespace Shopware\CustomModels\AsignModels;
 
use Shopware\Components\Model\ModelEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
* Defines data model for Products
* 
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_4.3
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/

/**
 * @ORM\Entity
 * @ORM\Table(name="asign_product")
 */
class Product extends ModelEntity
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
     * @var integer $artid
     *     
     * @ORM\Column(type="integer")     
     */
    private $artid;
   
    /**
     * @var integer $lastSent
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $lastSent = false;

    /**
     * @var string $ycResponse
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $ycResponse = null;

    /**
     * @var string $ycReference
     *
     * @ORM\Column()
     */
    private $ycReference;

    /**
     * @var \DateTime $createDate
     *
     * @ORM\Column(type="date")
     */
    private $createDate = null;
        
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $artid
     */
    public function setArtid($artid)
    {
        $this->artid = $artid;
    }

    /**
     * @return int
     */
    public function getArtid()
    {
        return $this->artid;
    }

    /**
     * @param integer $batchreq
     */
    public function setBatchreq($batchreq)
    {
        $this->batchreq = $batchreq;
    }

    /**
     * @return integer
     */
    public function getBatchreq()
    {
        return $this->batchreq;
    }

    /**
     * @param integer $noflag
     */
    public function setNoflag($noflag)
    {
        $this->noflag = $noflag;
    }

    /**
     * @return integer
     */
    public function getNoflag()
    {
        return $this->noflag;
    }

    /**
     * @param string $expdatetype
     */
    public function setExpdatetype($expdatetype)
    {
        $this->expdatetype = $expdatetype;
    }

    /**
     * @return string
     */
    public function getExpdatetype()
    {
        return $this->expdatetype;
    }

    /**
     * @param string $altunitiso
     */
    public function setAltunitiso($altunitiso)
    {
        $this->altunitiso = $altunitiso;
    }

    /**
     * @return string
     */
    public function getAltunitiso()
    {
        return $this->altunitiso;
    }

    /**
     * @param string $eantype
     */
    public function setEantype($eantype)
    {
        $this->eantype = $eantype;
    }

    /**
     * @return string
     */
    public function getEantype()
    {
        return $this->eantype;
    }

    /**
     * @param string $netto
     */
    public function setNetto($netto)
    {
        $this->netto = $netto;
    }

    /**
     * @return string
     */
    public function getNetto()
    {
        return $this->netto;
    }

    /**
     * @param string $brutto
     */
    public function setBrutto($brutto)
    {
        $this->brutto = $brutto;
    }

    /**
     * @return string
     */
    public function getBrutto()
    {
        return $this->brutto;
    }

    /**
     * @param string $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string $volume
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;
    }

    /**
     * @return string
     */
    public function getVolume()
    {
        return $this->volume;
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
     * @param \DateTime $createDate
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }     

    /**
     * Stores additional information from Articles
     *
     * @param string  $sData        Serialized data values
     * @param integer $id           Selected Row Id
     * @param integer $artid        Selected Article Id
     * @param integer $aIntHandling Internation shipping data
     *
     * @return null
     */
    public function saveAdditionalData($sData, $id, $artid, $aIntHandling)
    {
        // frame the columns...
        $blUpdate = false;
        $sColumns = "`id` = ''";               

        // insert / update asign_product table
        // push in db.. But first check if the data is alreay present!
        if ($id) {
            $iCount = Shopware()->Db()->query("select count(*) from `asign_product` where `id` = '" . $id . "' and `artid` = '". $artid . "'");    
            $blUpdate = true;
        }

        if ($iCount && $blUpdate) {
            $query = "update `asign_product` set `ycSpsDetails` = '" . $sData . "' where `id` = '" . $id . "' and `artid` = '" . $artid . "'";
        } else {
            $query = "insert into `asign_product` set `artid` = '" . $artid . "',`ycSpsDetails` = '" . $sData . "'";
        }       
        Shopware()->Db()->query($query);

        // update internation handling details on s_articles table
        $sArtQuery = "update `s_articles` set `tariff` = '" . $aIntHandling['tariff'] . "', `tara` = '" . $aIntHandling['tara'] . "', `origin` = '" . $aIntHandling['origin'] . "' where `id` = '" . $artid . "'";
        Shopware()->Db()->query($sArtQuery);
        
        // update order article information
        $this->updateHandlingInfo($aIntHandling, $artid);
    }

    /**
     * Updates Tara, Tariff and Origin details
     *
     * @param array $orderArticles Order articles
     *
     * @return null
     */
    public function updateHandlingInfo($aIntHandling, $artid)
    {
        $oOrder = new Orders();
        $oOrder->updateOrderArticlesHandlingInfo($aIntHandling, $artid);
    }
    
    /**
     * Get Handling inforamtion for the articleid
     *
     * @param integer $articleID article item id
     *
     * @return array
     */
    public function getHandlingInfo($articleID)
    {
        $aIntHandling = Shopware()->Db()->fetchRow("select `tara`, `tariff`, `origin` from `s_articles` where `id` = '" . $articleID . "'");
        return $aIntHandling;
    }

    /**
     * Returns article data based on artid
     *
     * @param integer $artid article item id
     * @param bool    $artid if its a CRON
     *
     * @return string
     */
    public function getArticleDetails($artid, $isCron = false)
    {
        $sSql = "SELECT s_articles.name as `name`, s_articles_details.articleID, s_articles_details.weight, s_articles_details.length, s_articles_details.width, s_articles_details.height, s_articles_details.ordernumber, s_articles_details.ean, s_articles_details.instock FROM s_articles";
        $sSql .= " JOIN s_articles_details ON s_articles.id = s_articles_details.articleID";
        
        // cron?
        if ($isCron) {
            $sSql .= " JOIN asign_product ON s_articles.id = asign_product.artid";
        }        
        
        $sSql .= " WHERE s_articles.id = '" . $artid . "' AND s_articles_details.kind = 1";
        
        // cron?
        if ($isCron) {
            $sSql .= " AND asign_product.ycReference = 0"; // include non-YC response
        }
        
        $result = Shopware()->Db()->fetchRow($sSql);
        if ($result) {
            // get translations
            $aTrans = Shopware()->Db()->fetchRow("SELECT s_articles_translations.name as `altname`, s_articles_translations.languageID, s_core_locales.locale as `altlang` FROM `s_articles_translations` JOIN `s_core_locales` ON s_articles_translations.languageID = s_core_locales.id WHERE s_articles_translations.articleID = '".$result['articleID']."'");
           
            // set multilang value
            $result['pronames'][] = array(
                    'lang' => Shopware()->Db()->fetchOne("select `locale` from `s_core_locales` where `id` <> '" . $result['languageID'] . "'"),
                    'name' => $result['name']
            );
            
            if ($aTrans) {
                $result['pronames'][] = array(
                    'lang' => $aTrans['altlang'],
                    'name' => $aTrans['altname']
                );
            }

            return $result;
        }        
    }

    /**
     * Return YC values..
     *
     * @param string $artid article item id
     *
     * @return string
     */
    public function getYCDetailsForThisArticle($artid)
    {        
        $result = Shopware()->Db()->fetchOne("select `ycSpsDetails` from `asign_product` where `artid` = '" . $artid . "'");
        $aParams = unserialize($result);
        
        // forgot to add columns: temp values
        if ($aParams) {
            $aParams['altnum'] = 1;
            $aParams['altdeno'] = 1;

            return $aParams;    
        }
        
        return null;
    }

    /**
     * Function to save the Response
     * received from Yellowcube 
     *
     * @param array $aResponseData Array of response     
     * @param string $artid        Article id
     *
     * @return null
     */
    public function saveResponseData($aResponseData, $artid)
    {
        // if response is not "E" then?
        if ($aResponseData['StatusType'] !== 'E') {
            $sReference = ", `ycReference` = '" . $aResponseData['Reference'] . "'";
        }
        
        // serialize the data
        $sData = serialize($aResponseData);
               
        // update reference number, but first check if alreay entry?
        $iCount = Shopware()->Db()->fetchOne("select count(*) from `asign_product` where `artid` = '" . $artid . "'");
        
        // if present then?        
        if ($iCount) {
            $sQuery = "update `asign_product` set `lastSent` = 1, `ycResponse` = '" . $sData ."'" . $sReference . " where `artid` = '" . $artid . "'";
        } else {
            $sQuery = "insert into `asign_product` set `artid` = '" . $artid . "', `lastSent` = 1, `ycResponse` = '" . $sData . "'" . $sReference;
        }

        Shopware()->Db()->query($sQuery);
    }
}
