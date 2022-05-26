<?php 
require 'config.php';

setcookie('id_of_website', '', strtotime("+1 year")); 
setcookie('key_of_website', '', strtotime("+1 year"));

if(isset($_COOKIE['logID'], $_COOKIE['logKey'])){ 
    $logID = $_COOKIE['logID']; 
    $logKey = $_COOKIE['logKey']; 
    $result = $conn->query("SELECT * FROM loginfo WHERE logID = '$logID' AND securityKey='$logKey'");
    if ($result->num_rows > 0) {
        $log_data = $result->fetch_assoc();
        $logID = $log_data['logID'];
        $conn->query("DELETE FROM loginfo WHERE logID='$logID'");
    }
}

header("Location: index.php");
exit();