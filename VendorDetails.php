<?php

include_once './Db/DbHandler.php';
require_once("./SimpleRest.php");
require_once("./function/Constants.php");
require_once("./function/validation.php");
require_once './model/user.php';
require_once './fcm.php';

class VendorDetails extends SimpleRest {

    static $inst = null;

    public static function Instance() {

        if (VendorDetails::$inst === null) {
            VendorDetails::$inst = new VendorDetails();
        }
        return VendorDetails::$inst;
    }

    function __construct() {
        $this->handler = new DbHandler();
        $this->requestContentType = 'application/json';
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: X-Requested-With, content-type, access-control-allow-origin, Authorization, access-control-allow-methods, access-control-allow-headers');
    }

    function login() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           
            $email = $_POST["emailId"];
            $password = $_POST["password"];
            //echo $this->encodeJson($this->user);
            if (!empty($email) && !empty($password)) {
                $rawData = $this->handler->vendorLogin(trim($email), trim($password));
                if (!empty($rawData)) {
                    // $this->handler->updateStoreOpen(1, $rawData["userId"]);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = $rawData;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Not Available";
                }
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                
                $result["data"] =null;
                $result["code"] = "01";
                $result["message"] = "Empty Request";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
        echo $this->encodeJson($result);
    }

    function orderDetails() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $storeKey = trim($_POST["storeKey"]);
            if (!empty($storeKey)) {
                $rawData = $this->handler->vendorCartDetails($storeKey);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Empty Request";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
        echo $this->encodeJson($result);
    }

    function orderDetailsByVendorId() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = trim($_POST["cartId"]);
            $storeKey = trim($_POST["storeKey"]);
            if (!empty($storeKey) && !empty($cartId)) {
                $this->handler->updateIsSeen($cartId);
                $rawData = $this->handler->vendorCartId($cartId);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Empty Request";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
    
    function orderConfirmed() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = trim($_POST["cartId"]);
            $storeKey = trim($_POST["storeKey"]);
            $rawData = $this->handler->vendorCartId($cartId);
            $fcmId = $this->handler->getCustomerFcmKey($cartId);
            if (!empty($rawData)) {
                foreach ($rawData as $value) {
                    $storeDetails = $this->handler->getSellQuantity($value["cartDetailsId"]);
                    $total = $storeDetails["sellQuantity"] + $value["quantity"];
                    $this->handler->updateQuantity($total, $storeDetails["vendorId"], $storeDetails["userId"]);
                }
                $cnt = $this->handler->updateCartOrderStatus(3, $cartId);
                if ($cnt > 0) {
                    sendFcm($fcmId, "Your Order Is Processing");
                }
                $data = $this->handler->orderDetailsByCartId($cartId);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $data;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = NULL;
                $result["code"] = "01";
                $result["message"] = "Success";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }

    function orderDispatch() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = trim($_POST["cartId"]);
            $storeKey = trim($_POST["storeKey"]);
            $fcmId = $this->handler->getCustomerFcmKey($cartId);
            $cnt = $this->handler->updateCartOrderStatus(4, $cartId);
            if ($cnt > 0) {
                sendFcm($fcmId, "Order Dispatched and on the way");
            }
            $data = $this->handler->orderDetailsByCartId($cartId);
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = $data;
            $result["code"] = "00";
            $result["message"] = "Success";
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
    
    function vendorRemoveProduct(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           $cartId = trim($_POST["cartId"]);
           $storeKey = trim($_POST["storeKey"]);
           $cartDetailsId=trim($_POST["cartDetailsId"]);
           $cnt = $this->handler->countCartDetailsId($cartDetailsId);
           if($cnt>0){
               $this->handler->updateCartDetails($cartDetailsId);
               $total=$this->handler->getAllAmount($cartId);
               $inactive=$this->handler->getAllInActiveAmount($cartId);
               $this->handler->updateCartAmount($total-$inactive,$cartId);
               $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = ["amount"=>$total-$inactive];
            $result["code"] = "00";
            $result["message"] = "".$total-$inactive;
           }
           else{ 
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Invalid id";
           }
        } 
        else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
    
    function orderDelivered() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cartId = trim($_POST["cartId"]);
            $storeKey = trim($_POST["storeKey"]);
            $fcmId = $this->handler->getCustomerFcmKey($cartId);
            $cnt = $this->handler->updateCartOrderStatus(6, $cartId);
            if ($cnt > 0) {
                sendFcm($fcmId, "Your Order Is Delivered");
            }
            $data = $this->handler->orderDetailsByCartId($cartId);
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = $data;
            $result["code"] = "00";
            $result["message"] = "Success";
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }

    function vendordatadetails() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $storeKey = trim($_POST["storeKey"]);
            if (!empty($storeKey)) {
                $rawData = $this->handler->vendorDataDetails($storeKey);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Empty Request";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        echo $this->encodeJson($result);
    }

    function vendorupdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = json_decode(file_get_contents('php://input'), true);
            if ($this->handler->countVendorId($user["userId"]) > 0) {
                $this->handler->updateVendorInfo($user["name"], $user["mobileNumber"], $user["address"], $user["userId"]);
                $rawData = $this->handler->vendorDetails($user["userId"]);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Not Exist.";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        echo $this->encodeJson($result);
    }
function vendorproductupdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product = json_decode(file_get_contents('php://input'), true);
             $userId = $_GET["userId"];
            //$product["productDetailsId"];
            if ($this->handler->countVendorProductId($product["productDetailsId"],$userId) > 0) {
                $rawData = $this->handler->updateProductByVendor($product["sellingPrice"], $product["costPrice"], $product["totalQuantity"], $product["productDetailsId"]);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Not Exist.";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        echo $this->encodeJson($result);
    }
    
function vendorproductupdateData() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product = json_decode(file_get_contents('php://input'), true);
             $userId = $_GET["userId"];
           if(!empty($product["categoryId"]) && !empty($product["productName"]) && !empty($product["quantity"]) && !empty($product["type"]) && !empty($product["sellingPrice"]) && !empty($product["productDetailsId"])){
                if ($this->handler->countVendorProductId($product["productDetailsId"],$userId) > 0) {
                    if(isset($product["isActive"]))
                    $active=$product["isActive"];
                    else
                    $active=1;
                $rawData = $this->handler->updateProduct($product["categoryId"],$product["productName"],$product["quantity"],$product["type"],$product["sellingPrice"],$active,$product["productDetailsId"]);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $rawData;
                $result["code"] = "00";
                $result["message"] = "Success";
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Not Exist.";
            }
           }
           else{
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Required Parameter Missing";
           }
           
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        echo $this->encodeJson($result);
    }
    function addProduct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product = json_decode(file_get_contents('php://input'), true);
            //$product["productDetailsId"];
            $userId = $_GET["userId"];
            if (!empty($product) && !empty($product["categoryId"]) && $product["categoryId"]>0 && !empty($product["productName"]) && !empty($product["quantity"]) && !empty($product["type"]) && !empty($product["sellingPrice"])) {
                if ($this->handler->checkProduct(trim($product["productId"])) > 0) {
                    if ($this->handler->countVendorProductId($product["productDetailsId"], $userId) == 0) {
                        $rawData = $this->handler->insertProducttoVendor($product["productDetailsId"], $product["sellingPrice"], $userId, 1, 1);
                        $this->statusCode = 200;
                        $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                        $result["data"] = $rawData;
                        $result["code"] = "00";
                        $result["message"] = "Success";
                    } else {
                        $this->statusCode = 200;
                        $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                        $result["data"] = null;
                        $result["code"] = "01";
                        $result["message"] = "Already Exist";
                    }
                } else {
                    if($this->handler->countProduct($product["productName"],$product["quantity"],$product["type"],$product["categoryId"])==0){
                  $insertId = $this->handler->insertProduct($product["categoryId"], $product["productName"]);
                    $insertId = $this->handler->insertProductDetails($product["quantity"], $insertId, $product["type"]);
                    $rawData = $this->handler->insertProducttoVendor($insertId, $product["sellingPrice"], $userId, 1, 1);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $cnt=$this->handler->countProduct($product["productName"],$product["quantity"],$product["type"],$product["categoryId"]);
                    $result["data"] = $cnt;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                    }
                    else{
                        $result["data"] =null;
                        $result["code"] = "00";
                        $result["message"] = "Duplicate Data"; 
                    }
                }
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Empty Request";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
            //echo $this->encodeJson($result);
        }
        echo $this->encodeJson($result);
    }

    function updateFcmId() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["userId"]) && isset($_POST["fcmId"])) {
                $userId = trim($_POST["userId"]);
                $fcmId = trim($_POST["fcmId"]);
                if (!empty($userId) && $this->handler->countVendorId($userId) == 1) {
                    $this->handler->updateVFcmId($fcmId, $userId);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 404;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Vendor Does not exist";
                }
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Requird Field Empty";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }

    function logout() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["userId"])) {
                $userId = trim($_POST["userId"]);
                if (!empty($userId) && $this->handler->countVendorId($userId) == 1) {
                    $this->handler->updateStoreOpen(0, $userId);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 404;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Vendor Does not exist";
                }
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Requird Field Empty";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }

    function vendorPayment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["userId"])) {
                $userId = trim($_POST["userId"]);
                $paymentId=$_POST["payment"];
                if (!empty($userId) && $this->handler->countVendorId($userId) == 1) {
                    $this->handler->updateStoreOpen($paymentId, $userId);
                     $data=$this->handler->vendorDetaild($userId);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = $data;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 404;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Vendor Does not exist";
                }
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Requird Field Empty";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
function vendorShopOpen() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["userId"])) {
                $userId = trim($_POST["userId"]);
                if (!empty($userId) && $this->handler->countVendorId($userId) == 1) {
                    $this->handler->updateStoreOpen(1, $userId);
                     $data=$this->handler->vendorDetaild($userId);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = $data;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 404;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Vendor Does not exist";
                }
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Requird Field Empty";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
    function vendorStatus() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["userId"])) {
                $userId = trim($_POST["userId"]);
                if (!empty($userId) && $this->handler->countVendorId($userId) == 1) {
                    $data=$this->handler->vendorDetaild($userId);
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = $data;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 404;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "Vendor Does not exist";
                }
            } else {
                $this->statusCode = 404;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] = "Requird Field Empty";
            }
        } else {
            $this->statusCode = 404;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
        echo $this->encodeJson($result);
    }
    
    function generateReport() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST["storeKey"]) && isset($_POST["startDate"]) && isset($_POST["endDate"]) && isset($_POST["type"])) {
                $type = $_POST["type"];
                if ($type == 1) {
                    $data = $this->handler->reportWithAll($_POST["storeKey"], $_POST["startDate"], $_POST["endDate"], $type);
                } else {
                     $data = $this->handler->reportWithType($_POST["storeKey"], $_POST["startDate"], $_POST["endDate"], $type);
                }
                if (!empty($data)) {
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = $data;
                    $result["code"] = "00";
                    $result["message"] = "Success";
                } else {
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                    $result["data"] = null;
                    $result["code"] = "01";
                    $result["message"] = "No record Found";
                }
            } else {
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] ="Parameter Missing";
            }
        } else {
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";
        }
         echo $this->encodeJson($result);
    }
    
    function updateCategory(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $category = json_decode(file_get_contents('php://input'), true);
             if(!empty($category["categoryName"]) && !empty($category["id"])){
                 $count=$this->handler->countCategory($category["categoryName"],$category["id"]);
                 if($count==0){
                    $chk=$this->handler->updateCategory($category["categoryName"],$category["active"],$category["id"]);
                $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $chk;
                $result["code"] = "00";
                $result["message"] ="Success";
                 }
                 else{
                      $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] ="Duplicate Data";
                 }
             }
             else{
                 $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] ="Parameter Missing";
             }
            
        }
        else{
           $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported"; 
        }
         echo $this->encodeJson($result);
    }
    
    function addCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $category = json_decode(file_get_contents('php://input'), true);
             if(!empty($category["categoryName"])){
                $count= $this->handler->countCategoryAdd($category["categoryName"]);
                 if($count==0){
                    $chker= $this->handler->addCategory($category["categoryName"],$category["active"]);
                     $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = $chker;
                $result["code"] = "00";
                $result["message"] ="Success";
                 }
                else{
                    $this->statusCode = 200;
                    $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] ="Duplicate Entry";
             }
             }
              else{
                 $this->statusCode = 200;
                $this->setHttpHeaders($this->requestContentType, $this->statusCode);
                $result["data"] = null;
                $result["code"] = "01";
                $result["message"] ="Parameter Missing";
             }
        }
        else{
            $this->statusCode = 200;
            $this->setHttpHeaders($this->requestContentType, $this->statusCode);
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "Get Request Not Supported";  
        }
         echo $this->encodeJson($result);
    }
    
    function categoryAll(){
         $rawData = $this->handler->getAllCategorys();
        if (empty($rawData)) {
            $this->statusCode = 200;
            $result["data"] = null;
            $result["code"] = "01";
            $result["message"] = "No Data Found";
            //$rawData = array('code' => "01",'message'=>'No Data Available');		
        } else {
            $this->statusCode = 200;
            $result["data"] = $rawData;
            $result["code"] = "00";
            $result["message"] = "success";
        }

        $this->setHttpHeaders($this->requestContentType, $this->statusCode);

        if (strpos($this->requestContentType, 'application/json') !== false) {
            $response = $this->encodeJson($result);
            echo $response;
        }
    }
    
}
