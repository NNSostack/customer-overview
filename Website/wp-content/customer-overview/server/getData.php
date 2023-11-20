<?php
include_once("common.php");

if(isset($_POST["getData"])){
    $getData = $_POST["getData"];
    echo loadData($getData);
}

