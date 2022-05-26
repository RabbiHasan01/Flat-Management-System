<?php
defined( 'ROOTPATH' )       || define( 'ROOTPATH', __DIR__ );
defined( 'HOMEURL' )        || define( 'HOMEURL', 'http://localhost/flat' );
defined( 'DB_SERVER' )      || define( 'DB_SERVER', 'localhost' );
defined( 'DB_USERNAME' )    || define( 'DB_USERNAME', 'flat' );
defined( 'DB_PASSWORD' )    || define( 'DB_PASSWORD', '123' );
defined( 'DB_NAME' )        || define( 'DB_NAME', 'flat' );
 
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
 
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
?>