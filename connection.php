<?php
//connection to database 
$hostname = 'localhost'; 
$database = 'db_todo';
$dbuser = 'root';
$dbpassword = '';
$conn = new mysqli($hostname, $dbuser, $dbpassword, $database);
if ($conn->connect_error) {
    die('Could not connect to DB server on $dbhostname'
            . $conn->connect_error);
}
/*else {
   // echo 'Connected to the database';
}
?>*/