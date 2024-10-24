<?php
$host = 'localhost';
 $port = 5432; 
 $user = 'postgres';
 $pass = '78605533';
 $db = 'medinfo';

 $conn = pg_connect( "host=$host port=$port
           dbname=$db user=$user password=$pass");
 if ($conn === false) {
  echo 'Connection failed';
  exit;
 }
 
 echo 'Connected to the database.';
 pg_close($conn);