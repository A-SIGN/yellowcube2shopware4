<?php

/**
 * This file defines the Backend controller
 *
 * PHP version 5
 * 
 * @category  asign
 * @package   AsignYellowcube_v2.0_CE_4.3
 * @author    entwicklung@a-sign.ch
 * @copyright asign
 * @license   http://www.a-sign.ch/
 * @version   2.0
 * @link      http://www.a-sign.ch/
 * @see       Shopware_Controllers_Backend_AsignYellowcube
 * @since     File available since Release 1.0
 */

use Shopware\AsignYellowcube\Components\Api\AsignYellowcubeCore;
use Shopware\AsignYellowcube\Components\Api\AsignYellowcubeCron;
use Shopware\AsignYellowcube\Helpers\ApiClasses\AsignSoapClientApi;

use Shopware\CustomModels\AsignModels\Product;
use Shopware\CustomModels\AsignModels\Orders;
use Shopware\CustomModels\AsignModels\Inventory;
use Shopware\CustomModels\AsignModels\Errorlogs;

/**
* Defines backend controller
* 
* @category Asign
* @package  AsignYellowcube_v2.0_CE_4.3
* @author   entwicklung@a-sign.ch
* @link     http://www.a-sign.ch
*/
class Shopware_Controllers_Backend_AsignYellowcube extends Shopware_Controllers_Backend_ExtJs
{   
    /**
     * Returns stock value for the inventory
     *   
     * @return integer value
     */
    protected $_iStockValue = null; 
    
    /**
     * Returns all the products based on filter or sort.
     *   
     * @return array
     */
    public function getProductsAction()
    {      
        $offset = $this->Request()->getParam('start', 0);
        $limit  = $this->Request()->getParam('limit', 100);
          
        $select = Shopware()->Db()->select()
                ->from('s_articles', array('artid' => 'id', 'name', 'active', 'tara', 'tariff', 'origin'))
                ->joinLeft('s_articles_details', 's_articles.id = s_articles_details.articleID', array('ordernumber','instock','active'))
                ->joinLeft('asign_product', 'asign_product.artid = s_articles_details.articleID', array('id','lastSent','ycSpsDetails','ycResponse','ycReference','createDate'));
                
        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {
                $select->where('s_articles.name LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('s_articles_details.ordernumber LIKE ?', '%' . $filter["value"] . '%');
            }
        }

        // add sorting features...
        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = reset($sort);
            $column = $sorting['property'];
            $direction = $sorting['direction'];
            switch ($column) {
                case 'artnum':
                    $select->order('s_articles_details.ordernumber ' . $direction);
                    break;
                case 'name':
                    $select->order('s_articles.name ' . $direction);
                    break;
                case 'inStock':
                    $select->order('s_articles_details.instock ' . $direction);
                    break;
                case 'active':
                    $select->order('s_articles.active ' . $direction);
                    break;
                case 'ycReference':
                    $select->order('asign_product.ycReference ' . $direction);
                    break;
                case 'timestamp':
                    $select->order('asign_product.createDate ' . $direction);
                    break;
                default:
                    $select->order('s_articles.name ' . $direction);
            }
        } else {
            $select->order('s_articles.name');
        }
        
        // set the paginator and result
        $paginator = new Zend_Paginator_Adapter_DbSelect($select);
        $totalCount = $paginator->count();
        $result = $paginator->getItems($offset, $limit);
        
        $data = array();
        foreach ($result as $key => $product) {
            // get the sps params
            $aData = unserialize($product['ycSpsDetails']);
            $aRsp = unserialize($product['ycResponse']);
            $isAccepted = $this->isResponseAccepted($aRsp);
            $aResponse = $this->getJsonEncodedData($aRsp);

            // fill in the blocks
            $data[$key]['id']           = $product["id"];
            $data[$key]['artid']        = $product["artid"];
            $data[$key]['artnum']       = $product["ordernumber"];
            $data[$key]['name']         = $product["name"];
            $data[$key]['inStock']      = $product["instock"];
            $data[$key]['active']       = $product["active"];
            $data[$key]['lastSent']     = $product["lastSent"];
            $data[$key]['ycResponse']   = $aResponse;
            $data[$key]['ycReference']  = $product["ycReference"];
            $data[$key]['timestamp']   = $product["createDate"];

            $data[$key]['batchreq']     = $aData["batchreq"];
            $data[$key]['noflag']       = $aData["noflag"];
            $data[$key]['expdatetype']  = $aData["expdatetype"];
            $data[$key]['altunitiso']   = $aData["altunitiso"];
            $data[$key]['eantype']      = $aData["eantype"];
            $data[$key]['netto']        = $aData["netto"];
            $data[$key]['brutto']       = $aData["brutto"];
            $data[$key]['length']       = $aData["length"];
            $data[$key]['width']        = $aData["width"];
            $data[$key]['height']       = $aData["height"];
            $data[$key]['volume']       = $aData["volume"];
            $data[$key]['tariff']       = $product["tariff"];
            $data[$key]['tara']         = $product["tara"];
            $data[$key]['origin']       = $product["origin"];
            $data[$key]['isaccepted']   = $isAccepted;                
        }
     
        $this->View()->assign(array('data' => $data, 'success' => true, 'total' => $totalCount));
    }

    /**
     * Returns all the orders based on filter or sort.
     *   
     * @return array
     */
    public function getOrdersAction()
    {       
        $offset = $this->Request()->getParam('start', 0);
        $limit  = $this->Request()->getParam('limit', 100);
        
        // is the manual order sending enabled?
        $isManual = Shopware()->Plugins()->Backend()->AsignYellowcube()->Config()->blYellowCubeOrderManualSend;

        $select = Shopware()->Db()->select()
            ->from('s_order', array('ordid' => 'id', 'ordernumber','invoice_amount', 'invoice_amount_net','ordertime', 'userID'))
            ->joinLeft('s_core_paymentmeans', 's_order.paymentID = s_core_paymentmeans.id', array('payment' => 'description'))
            ->joinLeft('s_premium_dispatch', 's_order.dispatchID = s_premium_dispatch.id', array('shipping' => 'name'))
            ->joinLeft('s_core_states', 's_order.status = s_core_states.id', array('status' => 'description'))
            ->joinLeft('asign_orders', 'asign_orders.ordid = s_order.id', array('id','lastSent','ycReference','ycResponse','ycWabResponse','ycWarResponse'))
            ->where('s_order.ordernumber > 0');
            
        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {                
                $select->andWhere('s_order.ordernumber LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('s_premium_dispatch.name LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('s_core_paymentmeans.description LIKE ?', '%' . $filter["value"] . '%');
            }
        }

        // add sorting features...
        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = reset($sort);
            $column = $sorting['property'];
            $direction = $sorting['direction'];
            
            switch ($column) {
                case 'timestamp':
                    $select->order('s_order.ordertime ' . $direction);
                    break;
                case 'orderNumber':
                    $select->order('s_order.ordernumber ' . $direction);
                    break;
                case 'amount':
                    $select->order('s_order.invoice_amount ' . $direction);
                    break;
                case 'payment':
                    $select->order('s_core_paymentmeans.description ' . $direction);
                    break;
                case 'shipping':
                    $select->order('s_premium_dispatch.name ' . $direction);
                    break;
                case 'status':
                    $select->order('s_core_states.description ' . $direction);
                    break;
                case 'ycReference':
                    $select->order('asign_orders.ycReference ' . $direction);
                    break;
                default:
                    $select->order('s_articles.name ' . $direction);
            }
        } else {
            $select->order('s_order.ordertime DESC'); 
        }
        
        // set the paginator and result
        $paginator = new Zend_Paginator_Adapter_DbSelect($select);
        $totalCount = $paginator->count();
        $result = $paginator->getItems($offset, $limit);
        
        $data = array();
        foreach ($result as $key => $order) {
            // frame serialized responses
            $aData = unserialize($order["ycResponse"]);
            $aResponse = $this->getJsonEncodedData($aData);

            // get EORI data
            $sEORI = Orders::getOrderEoriNumber($order['userID']);

            // WAB response
            $wData = unserialize($order["ycWabResponse"]);
            $isWabAccepted = ($wData['StatusType'] === 'S' && $wData['StatusCode'] === 10) ? 1 : 0;
            $isWarAccepted = ($wData['StatusType'] === 'S' && $wData['StatusCode'] === 100) ? 1 : 0;
            $aWabResponse = $this->getJsonEncodedData($wData);
            
            //modified version of the WAR response, since it has items information
            $warResponse = unserialize($order["ycWarResponse"]);                
            $warResponse = $warResponse[WAR]->GoodsIssue;

            $warMergeData['GoodsIssueHeader'] = (array)$warResponse->GoodsIssueHeader;
            $warMergeData['CustomerOrderHeader'] = (array)$warResponse->CustomerOrderHeader;

            $aResponseItems = null;
            $customerOrderDetail = (array)$warResponse->CustomerOrderList->CustomerOrderDetail;
            $BVPosNo = $customerOrderDetail['BVPosNo'];

            // if the count of the array is only one? decide by finding the first element
            if ($BVPosNo) {
                $aResponseItems[0] = $customerOrderDetail;
            } else {
                foreach ($customerOrderDetail as $items) {
                    $aResponseItems[] = (array)$items;
                }    
            }                
            $warMergeData['CustomerOrderList'] =  $aResponseItems;
            
            $data[$key]['id']               = $order["id"];
            $data[$key]['ordid']            = $order["ordid"];
            $data[$key]['orderNumber']      = $order["ordernumber"];
            $data[$key]['amount']           = $order["invoice_amount"];
            $data[$key]['amountNet']        = $order["invoice_amount_net"];
            $data[$key]['payment']          = $order["payment"];
            $data[$key]['shipping']         = $order["shipping"];
            $data[$key]['status']           = $order["status"];
            $data[$key]['lastSent']         = $order["lastSent"];
            $data[$key]['eori']             = $sEORI;
            $data[$key]['ycReference']      = $order["ycReference"];
            $data[$key]['ycResponse']       = $aResponse;
            $data[$key]['ycWabResponse']    = $aWabResponse;
            $data[$key]['ycWarResponse']    = json_encode($warMergeData);
            $data[$key]['timestamp']        = $order["ordertime"];
            $data[$key]['ycWarCount']       = count($aResponseItems);
            $data[$key]['ismanual']         = $isManual;
            $data[$key]['iswabaccepted']    = $isWabAccepted;
            $data[$key]['iswaraccepted']    = $isWarAccepted;
        }
        
        $this->View()->assign(array('data' => $data, 'success' => true, 'total' => $totalCount));
    }

    /**
     * Returns all the inventory based on filter or sort.
     *   
     * @return array
     */
    public function getInventoryAction()
    {
        $offset = $this->Request()->getParam('start', 0);
        $limit  = $this->Request()->getParam('limit', 100);
        
        $select = Shopware()->Db()->select()
                ->from('asign_yellowcube');
                
        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {                
                $select->where('asign_yellowcube.ycarticlenr LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('asign_yellowcube.articlenr LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('asign_yellowcube.artdesc LIKE ?', '%' . $filter["value"] . '%');
            }
        }
        
        // sortin the inventory list
        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = reset($sort);
            switch ($sorting['property']) {
                case 'ycarticlenr':
                    $select->order('asign_yellowcube.ycarticlenr', $sorting['direction']);
                    break;
                case 'articlenr':
                    $select->order('asign_yellowcube.articlenr', $sorting['direction']);
                    break;
                case 'artdesc':
                    $select->order('asign_yellowcube.artdesc', $sorting['direction']);
                    break;
                default:
                    $select->order('asign_yellowcube.createdon', 'DESC');
            }
        } else {
            $select->order('asign_yellowcube.artdesc', 'DESC');
        }

        // set the paginator and result
        $paginator = new Zend_Paginator_Adapter_DbSelect($select);
        $totalCount = $paginator->count();
        $result = $paginator->getItems($offset, $limit);

        $data = array();
        foreach ($result as $key => $inventory) {
            // unserialize and get values            
            $ycAdditional =  $this->getJsonEncodedData(unserialize($inventory["additional"]));

            // set parameters
            $data[$key]['id']           = $inventory["id"];
            $data[$key]['ycarticlenr']  = $inventory["ycarticlenr"];
            $data[$key]['articlenr']    = $inventory["articlenr"];
            $data[$key]['artdesc']      = $inventory["artdesc"];            
            $data[$key]['timestamp']    = $inventory["createdon"];
            $data[$key]['stockvalue']   = $this->_iStockValue;
            $data[$key]['additional']   = $ycAdditional;
        }

        $this->View()->assign(array('data' => $data, 'success' => true, 'total' => $totalCount));
    }

    /**
     * Returns all the logs based on filter or sort.
     *   
     * @return array
     */
    public function getLogsAction()
    {
        $offset = $this->Request()->getParam('start', 0);
        $limit  = $this->Request()->getParam('limit', 100);
        
        $select = Shopware()->Db()->select()
                ->from('asign_logs');
                
        //If a filter is set
        if ($this->Request()->getParam('filter')) {
            //Get the value itself
            $filters = $this->Request()->getParam('filter');
            foreach ($filters as $filter) {                
                $select->where('asign_logs.type LIKE ?', '%' . $filter["value"] . '%');
                $select->orWhere('asign_logs.message LIKE ?', '%' . $filter["value"] . '%');
            }
        }

        // add sorting features...
        $sort = $this->Request()->getParam('sort');
        if ($sort) {
            $sorting = reset($sort);
            $column = $sorting['property'];
            $direction = $sorting['direction'];
            
            switch ($column) {
                case 'logtype':
                    $select->order('asign_logs.type ' . $direction);
                    break;
                case 'message':
                    $select->order('asign_logs.message ' . $direction);
                    break;
                case 'timestamp':
                    $select->order('asign_logs.createdon ' . $direction);
                    break;
                default:
                    $select->order('asign_logs.createdon ' . $direction);
            }
        } else {
            $select->order('asign_logs.createdon DESC'); 
        }
               
        // set the paginator and result
        $paginator = new Zend_Paginator_Adapter_DbSelect($select);
        $totalCount = $paginator->count();
        $result = $paginator->getItems($offset, $limit);

        $data = array();
        foreach ($result as $key => $logs) {            
            $data[$key]['id']       = $logs["id"];
            $data[$key]['logtype']  = $logs["type"];
            $data[$key]['message']  = $logs["message"];
            $data[$key]['devlog']   = $logs["devlog"];
            $data[$key]['timestamp']  = $logs["createdon"];
        }
        
        $this->View()->assign(array('data' => $data, 'success' => true, 'total' => $totalCount));
    }

    /**
     * Saves additional information related to product
     *   
     * @return array
     */
    public function createAdditionalsAction()
    {
        // get update id...
        $updateId = $this->Request()->getParam('id');
        $articleId = $this->Request()->getParam('artid');
        try{           
            $aParams = array (
                'id'           => $this->Request()->getParam('id'),                
                'batchreq'     => $this->Request()->getParam('batchreq'),
                'noflag'       => $this->Request()->getParam('noflag'),
                'expdatetype'  => $this->Request()->getParam('expdatetype'),
                'altunitiso'   => $this->Request()->getParam('altunitiso'),
                'eantype'      => $this->Request()->getParam('eantype'),
                'netto'        => $this->Request()->getParam('netto'),
                'brutto'       => $this->Request()->getParam('brutto'),
                'length'       => $this->Request()->getParam('length'),
                'width'        => $this->Request()->getParam('width'),
                'height'       => $this->Request()->getParam('height'),
                'volume'       => $this->Request()->getParam('volume'),
                'createDate'   => date('Y-m-d') 
            );
            $sParams = serialize($aParams); // serialize and save

            // internation information
            $aIntHandling = array(
                'tariff'       => $this->Request()->getParam('tariff'), 
                'tara'         => (double)$this->Request()->getParam('tara'), 
                'origin'       => $this->Request()->getParam('origin')                 
            );

            // save the details
            $oModel = new Product();
            $oModel->saveAdditionalData($sParams, $updateId, $articleId, $aIntHandling);

            $this->View()->assign(array('success' => true));                                       
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Additional', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),  
                    'message' => $e->getMessage()
                )
            );
        }        
    }

    /**
     * Sends single article to Yellowcube based on options
     *
     * @return void
     */
    public function sendArticlesAction()
    {   
        // define parameters
        $artid = $this->Request()->getParam('artid');
        $mode  = $this->Request()->getParam('mode');

        // get article information based on ID
        $oModel = new Product();
        $aArticles = $oModel->getArticleDetails($artid);        

        try{
            $oYCube = new AsignYellowcubeCore();            
            if ($mode === "S") {
                $oResponse = $oYCube->getYCGeneralDataStatus($artid, "ART");
                $aResponse = (array)$oResponse;
            } else {
                $oResponse = $oYCube->insertArticleMasterData($aArticles, $mode);
                $sStatus = $oResponse['success'];
                $aResponse = (array)$oResponse['data'];
            }
                        
            $sStatusCode = $aResponse['StatusCode'];
            
            // get the serialized response
            $sTmpResult = $this->getSerializedResponse($aResponse); // to override the content

            // log it event if its success / failure
            $oModel->saveResponseData($aResponse, $artid); 
                
            // save in database
            if ($sStatus || $sStatusCode === 100) {
                $this->View()->assign(
                    array(
                        'success'       => true,
                        'mode'          => $mode,
                        'dataresult'    => $sTmpResult,
                        'statcode'      => $sStatusCode
                    )
                );
            } else {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'code'    => -1
                    )
                );
            }                                                                                                                                                                                                                                                                                                                         
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Product', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                )
            );
        }        
    }

    /**
     * Creates Order into Yellowcube datastore
     *
     * @return void
     */
    public function createOrderAction()
    {   
        // define parameters
        $ordid = $this->Request()->getParam('ordid');
        $mode  = $this->Request()->getParam('mode');

        // get article information based on ID
        $oModel = new Orders();
        $aOrders = $oModel->getOrderDetails($ordid);        

        try{
            $oYCube = new AsignYellowcubeCore();            
            if ($mode) {
                $oResponse = $oYCube->getYCGeneralDataStatus($ordid, $mode);
                $clrResponse = $aResponse = (array)$oResponse;                
            } else {                
                $aResponse = $oYCube->createYCCustomerOrder($aOrders);
                $clrResponse = $aResponse['data'];
            }
            
            $sStatusMsg  = $aResponse['success'];
            $sStatusType = $aResponse['StatusType'];
            $sStatusCode = $aResponse['StatusCode'];
            
            // check if any zip code error is linked?
            if ($aResponse['zcode']) {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'code'    => $aResponse['zcode'],
                        'message' => $aResponse['message']
                    )
                );
            } else {
                // get the serialized response
                $sTmpResult = $this->getSerializedResponse($clrResponse); // to override the content
            
                // log the response whether S or E                  
                $oModel->saveResponseData($aResponse, $ordid, $mode);

                // save in database
                if ($sStatusMsg || $sStatusType === 'S' || $sStatusCode === 100) {
                    $this->View()->assign(
                        array(
                            'success'       => true,
                            'dcount'        => 1,  
                            'mode'          => $mode,
                            'dataresult'    => $sTmpResult,
                            'statcode'      => $sStatusCode
                        )
                    );
                } else {
                    $this->View()->assign(
                        array(
                            'success' => false,
                            'code'    => -1
                        )
                    );
                }
            }         
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Order', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(), 
                    'message' => $e->getMessage()
                )
            );
        }        
    }

    /**
     * Saves EORI information for selected order
     *   
     * @return array
     */
    public function saveEoriAction()
    {        
        try{
            $orderId = $this->Request()->getParam('ordid');
            $eoriNumber = $this->Request()->getParam('eori');
            
            // save the details
            $oModel = new Orders();
            $oModel->saveOrderEoriNumber($orderId, $eoriNumber);

            $this->View()->assign(array('success' => true));                                       
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('EORI', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),  
                    'message' => $e->getMessage()
                )
            );
        }        
    }
    
    /**
     * Function to send only prepaid orders
     *
     * @return void
     */
    public function sendPrepaidAction()
    {
        try{
            $sMode = "pp"; // only prepayment
            $oCronObj = new AsignYellowcubeCron();
            $iCount = $oCronObj->autoSendYCOrders($sMode);

            // save in database 
            if ($iCount > 0) {                
                $this->View()->assign(
                    array(
                        'success' => true,
                        'dcount'  => $iCount
                    )
                );
            } else {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'code'    => -1
                    )
                );                
            }                                                                                                                                                                                                                                                                                                                         
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Prepaid', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                )
            );
        }        
    }
    
    /**
     * Function to send active/inactive articles
     *
     * @return void
     */
    public function sendProductAction()
    {
        try{
            $sMode = $this->Request()->getParam("optmode");
            $sFlag = Shopware()->Plugins()->Backend()->AsignYellowcube()->Config()->sCronArtFlag;
            
            $oCronObj = new AsignYellowcubeCron();
            $iCount = $oCronObj->autoInsertArticles($sMode, $sFlag);

            // save in database 
            if ($iCount > 0) {                
                $this->View()->assign(
                    array(
                        'success' => true,
                        'dcount'  => $iCount
                    )
                );
            } else {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'code'    => -1
                    )
                );                
            }                                                                                                                                                                                                                                                                                                                         
        } catch(Exception $e) {
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Product', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage()
                )
            );
        }        
    }

    /**
     * Updates inventory list by sending request to yellowcube
     *
     * @return void
     */
    public function updateListAction()
    {   
        try{
            $oYCube = new AsignYellowcubeCore();
            $aResponse = $oYCube->getInventory();

            // save in database 
            if ($aResponse['success']) {
                $oModel = new Inventory();
                $iCount = $oModel->saveInventoryData($aResponse['data']);

                $this->View()->assign(
                    array(
                        'success' => true,
                        'dcount'  => $iCount
                    )
                );
            } else {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'code'    => -1
                    )
                );                
            }                                                                                                                                                                                                                                                                                                                         
        } catch(Exception $e) {            
            $oLogs = new Errorlogs();
            $oLogs->saveLogsData('Inventory', $e);

            $this->View()->assign(
                array(
                    'success' => false,
                    'code'    => $e->getCode(),  
                    'message' => $e->getMessage()
                )
            );
        }        
    }
    
    /**
     * Performs CRON based YC actions
     *
     * @return null
     */
    public function cronAction()
    {        
        $aParams = explode(';', $this->Request()->getParam('opt'));
        $dbHashValue = Shopware()->Plugins()->Backend()->AsignYellowcube()->Config()->sYellowCubeCronHash;
        
        if ($this->Request()->getParam('hash') !== $dbHashValue) {
            header('HTTP/1.0 403 Forbidden');
            die('<h1>Forbidden</h1>You are not allowed to access this file!!'); 
        }        
        
        /**
         * Options for script:
         * 
         * co - Create YC Customer Order
         * ia - Insert Article Master Data
         *      ax  - Include only active
         *      ix    - Include only inactive
         *      xx  - Include all         
         *      I   - Insert article to yellowcube
         *      U   - Update article to yellowcube
         *      D   - Delete article from yellowcube
         * gi - Get Inventory
         */        
        $oCronObj = new AsignYellowcubeCron();
        $command = reset($aParams);

        switch ($command) {
        case 'co':  
            // only for prepayment: CashInAdvance/Vorouskasse is present
            $sMode = $aParams[1]; // payment - prepad (pp) only
            $oCronObj->autoSendYCOrders($sMode);
            break;
            
        case 'ia':  
            // applicable only for articles...
            $sMode = $aParams[1];// ax, ix, xx
            $sFlag = $aParams[2];//I, U, D

            // if no flags specified then use from module settings
            if ($sFlag == "") {
                $sFlag = "I";
            }
            $oCronObj->autoInsertArticles($sMode, $sFlag);
            break;
        
        case 'gi': $oCronObj->autoFetchInventory();
            break;
                
        default: echo "No options specified...";
            break;
        }
    }   

    /**
     * Retuns unserialized, reversed and json_encoded data 
     * for showing on views.
     *
     * @param array $aData - Array of data
     *
     * @return string
     */
    protected function getJsonEncodedData($aData)
    {        
        $aData = array_reverse($aData); // put in reverse order
        $jsonData = json_encode($aData); // encode as JSON data
        
        $this->_iStockValue = $aData['QuantityUOM']; // stock value is set

        return $jsonData;
    }
    
    /**
     * Checks if the response is finalized. Check for code=100
     *
     * @param string $aData - Array of Data
     *
     * @return string
     */
    protected function isResponseAccepted($aData)
    {
        $sCode = $aData['StatusCode'];
        $sType = $aData['StatusType'];
        
        if ($sType === 'S' && $sCode === 100) {
            return 1;
        } elseif ($sType === 'S' && $sCode === 10) {
            return 2;
        }
        
        return 0;
    }
    
    /**
     * Filter and send serialized data
     *
     * @param array  $aResponse - Array of Response data 
     *
     * @return string
     */
    protected function getSerializedResponse($aResponse)
    {
        $aResponse = (array)$aResponse;
        $aResponse = array_reverse($aResponse);// reverse the array
        return json_encode($aResponse);
    }

    protected function writeToFile($data)
    {
        $myfile = fopen("mylog.txt", "a+") or die("Unable to open file!");
        fwrite($myfile, print_r($data,1) . "\n==================================================\n");
        fclose($myfile);
    }
}
