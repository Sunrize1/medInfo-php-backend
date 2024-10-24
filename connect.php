<?php
$host = 'localhost';
 $port = 5432; 
 $user = 'postgres';
 $pass = '78605533';
 $db = 'medinfo';

 $conn = pg_connect( "host=$host port=$port
           dbname=$db user=$user password=$pass");
           
 if ($conn === false) {
    http_response_code(500);
    echo json_encode(["message" => "Connection failed"]);
    exit;
 }
 

