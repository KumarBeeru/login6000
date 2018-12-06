<?php
error_reporting(-1);
ini_set('display_errors','On');
  ini_set("allow_url_fopen", 1);
function get_query_result($qs, $url)
{
   
    $opts = array( 
        'http'=>array(
            'method'=>"POST",
            'header'=>array("Content-type: application/x-www-form-urlencoded\r\n".
                            "X-CyberPlat-Proto: SHA1RSA\r\n"),
            'content' => "inputmessage=".urlencode($qs)
        )
    ); 
  
    $context = stream_context_create($opts);   
   $ch = curl_init();
             curl_setopt($ch, CURLOPT_URL, $url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
             $Get_Response = curl_exec($ch);
             curl_close($ch);
   return $Get_Response;//file_get_contents($url, false, $context);
   
   //---------------------------------
   
  
}

function check_signature($response, $serverCert)
{
    $fields = preg_split("/END\r\nBEGIN SIGNATURE\r\n|END SIGNATURE\r\n|BEGIN\r\n/", $response, NULL, PREG_SPLIT_NO_EMPTY);
    //print_r($fields);
    if (count($fields) != 2) {
        print "Bad response\n";
		return;
    }

    $pubkeyid = openssl_pkey_get_public($serverCert);
    $ok = openssl_verify(trim($fields[0]), base64_decode($fields[1]), $pubkeyid);
    print "Signature is ";
    if ($ok==1) {
            print "good";
    } elseif ($ok==0) {
            print "bad";
    } else {
            print "ugly, error checking signature";
    }
    print "\n";
    openssl_free_key($pubkeyid);
}

//error_reporting(1);

define('CERT','795C8404A195B3695C9B4EC90A6C286332E8D5EC');  //our testing key 
define('CP_PASSWORD','Bizz@123'); //our pwd



//Live urls 
$check_url = "https://in.cyberplat.com/cgi-bin/instp/instp_pay_check.cgi";
        $pay_url = "https://in.cyberplat.com/cgi-bin/instp/instp_pay.cgi";
       
         $verify_url = "https://in.cyberplat.com/cgi-bin/status/get_status.cgi";

$SD = 328123;
$AP = 328327;
$OP = 328328;


$phNbr = "100189959";
$amount = "10";
//$account = "Surat";

$secKey = file_get_contents("private.key");
$passwd = CP_PASSWORD;
$serverCert = file_get_contents("mycert.pem");

$sessPrefix = rand(100, 300);
$sess = $sessPrefix.$phNbr.time();
$sess = substr($sess,-20);

 $querString="CERT=".CERT."\r\nSD=".$SD."\r\nAP=".$AP."\r\nOP=".$OP."\r\nSESSION=".$sess."\r\nType=5\r\nNUMBER=9574666688\r\nAMOUNT=1.0\r\nAMOUNT_ALL=1.0";
//echo $querString; \r\nNUMBER=$phNbr\r\nAMOUNT=$amount\r\nCOMMENT=Test

// make SHA1RSA signature
 $pkeyid = openssl_pkey_get_private($secKey, $passwd);
openssl_sign($querString, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
openssl_free_key($pkeyid);
//echo $signature;
$encoded = base64_encode($signature);
$encoded = chunk_split($encoded, 76, "\r\n");

$signInMsg = "BEGIN\r\n" . $querString . "\r\nEND\r\nBEGIN SIGNATURE\r\n" . $encoded . "END SIGNATURE\r\n";
//print "Signed request:\n$signInMsg\n";

// send request to Cyberplat
echo "\n==============Validation Response===================\n";

$response = get_query_result($signInMsg, $check_url);
//echo $response;
if ($response) {
    print "Server response:\n$response\n";
    // verify server signature
  check_signature($response, $serverCert);
} else {
    print "Bad response\n";
   
}





//echo "\n";
//print_r($signInMsg);
echo "\n============== Payment Response===================\n";

//exit();
// $recharge_response = get_query_result($signInMsg, $pay_url);

// if ($recharge_response) {
//     print "Server response:\n$recharge_response\n";
//     // verify server signature
//   check_signature($recharge_response, $serverCert);
// } else {
//     print "Bad response\n";
  
// }





$querString="CERT=".CERT."\r\nSD=$SD\r\nAP=$AP\r\nOP=$OP\r\nSESSION=$sess";

$pkeyid = openssl_pkey_get_private($secKey, $passwd);
openssl_sign($querString, $signature, $pkeyid, OPENSSL_ALGO_SHA1);
openssl_free_key($pkeyid);

$encoded = base64_encode($signature);
$encoded = chunk_split($encoded, 76, "\r\n");

$signInMsg = "BEGIN\r\n" . $querString . "\r\nEND\r\nBEGIN SIGNATURE\r\n" . $encoded . "END SIGNATURE\r\n";


echo "\n==============Payment Status Response=====================\n";

$response_status = get_query_result($signInMsg, $verify_url);
if ($response_status) {
    print "Server response:\n$response_status\n";
    // verify server signature
  check_signature($response_status, $serverCert);
} else {
    print "Bad response\n";
  
}		
?>


