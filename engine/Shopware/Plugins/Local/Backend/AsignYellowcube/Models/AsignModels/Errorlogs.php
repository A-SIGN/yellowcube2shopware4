<?php

/**
 * This file defines data model for Error logs
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
* Defines data model for error logs
* 
* @category A-Sign
* @package  AsignYellowcube_v2.0_CE_4.3
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
 
/**
 * @ORM\Entity
 * @ORM\Table(name="asign_logs")
 */
class Errorlogs extends ModelEntity
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
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $type;

    /**
     * @var string $message
     *
     * @ORM\Column(name="message", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $message;

    /**
     * @var string $devlog
     *
     * @ORM\Column(name="devlog", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $devlog;

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
     * Set type
     *
     * @param string $sType
     * @return Logs
     */
    public function setType($sType)
    {
        if (!empty($sType)) {
            $this->type = $sType;
        }
        return $this;
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set Message
     *
     * @param string $sMessage
     * @return Logs
     */
    public function setMessage($sMessage)
    {
        if (!empty($sMessage)) {
            $this->message = $sMessage;
        }
        return $this;
    }

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set Developer logs
     *
     * @param string $sDevlog
     * @return Logs
     */
    public function setDevlog($sDevlog)
    {
        if (!empty($sDevlog)) {
            $this->devlog = $sDevlog;
        }
        return $this;
    }

    /**
     * Get Developer log
     *
     * @return string
     */
    public function getDevlog()
    {
        return $this->devlog;
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
     * Stores logs information when error generated
     *
     * @param string $sType    Type of Error
     * @param object $oError   Exception error object
     * @param bool   $isDirect Is it non-object?
     *
     * @return null
     */
    public function saveLogsData($sType, $oError, $isDirect = false)
    {
        if (!$isDirect) {
            $sMessage = str_replace("'", '"', $oError->getMessage());
            $sDevlog  = str_replace("'", '"', $oError->__toString());            
        } else {
            $sMessage = str_replace("'", '"', $oError);
        }        

        $iSql = "insert into `asign_logs` set `type` = '" . $sType . "', `message` = '" . $sMessage . "', `devlog` = '" . $sDevlog . "', createdon = CURRENT_TIMESTAMP";
        Shopware()->Db()->query($iSql);
    }
}
