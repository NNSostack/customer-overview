<?php
include_once("common.php");

if($_GET["test"] == "1"){
    $statusPath = getDataPath() . "/status.json";

        if(!file_exists($statusPath)){
            $statusObj = new StdClass();
        }
        else{
            $json = stripslashes(str_replace(" ", "", str_replace(" ", "", file_get_contents($statusPath))));
            echo $json;
            
            $statusObj = json_decode($json);
        }

        print_r($statusObj);

        if(!$statusObj->companies){
            $statusObj->companies = new StdClass();
        }

        if(!$statusObj->companies->$hash){
            $statusObj->companies->$hash = new StdClass();
        }

        if(!$statusObj->companies->$hash->filesUploaded){
            $statusObj->companies->$hash->filesUploaded = new StdClass();
        }
        $statusObj->companies->$hash->filesUploaded->heppa = "hejsa";


        print_r($statusObj);

    return;
}


startApplication(false);

$getData = $_POST["getData"];

$curl = curl_init();
$url = 'https://handle-data.integrations.online-it-support.dk/handleData.ashx';

$postFields = null;

$postFile = false;

if(isset($getData)){
    $postFields = array("getData" => $getData, 'ApiKey' => getApiKey());
}
else{
    $postFile = true;
    $journalNo = $_GET["JournalNo"];
    $accountingYear = $_GET["AccountingYear"];
    $voucherNo = $_GET["VoucherNo"];
    $hash = $_GET["hash"];
    $file = $_FILES["file"];

    $curlFile = curl_file_create($file["tmp_name"], mime_content_type($file["tmp_name"]));
    $postFields = array("file" => $curlFile,'JournalNo' => $journalNo,'AccountingYear' => $accountingYear,'VoucherNo' => $voucherNo, 'Hash' => $hash, 'ApiKey' => getApiKey());
}

    //$filep = fopen("dump.log", "wb");
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_BINARYTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_VERBOSE => true,
      //CURLOPT_STDERR => $filep,
      CURLOPT_POSTFIELDS => $postFields,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: multipart/form-data'
      )
    ));


$response = curl_exec($curl);

if($postFile){
    if($response == "OK"){
        
        $statusPath = getDataPath() . "/status.json";

        if(!file_exists($statusPath)){
            $statusObj = new StdClass();
        }
        else{
            $json = stripslashes(str_replace(" ", "", str_replace(" ", "", file_get_contents($statusPath))));            
            $statusObj = json_decode($json);
        }

        if(!$statusObj->companies){
            $statusObj->companies = new StdClass();
        }

        if(!$statusObj->companies->$hash){
            $statusObj->companies->$hash = new StdClass();
        }

        if(!$statusObj->companies->$hash->filesUploaded){
            $statusObj->companies->$hash->filesUploaded = new StdClass();
        }

        $fileProp = "{$journalNo}-{$accountingYear}-{$voucherNo}";
        $statusObj->companies->$hash->filesUploaded->$fileProp = true;

        file_put_contents($statusPath, json_encode($statusObj));
    }
}

curl_close($curl);
echo $response;
