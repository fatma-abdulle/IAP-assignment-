<?php
print "Hello ,World";
print "<br>";
print "Today is".date("Y-m-d");
//Create database connection
$servername = "localhost";
$username="root";
$password="1234";
$dbname="trial";

$conn = new mysqli($servername, $username, $password, $dbname);



//Check connection'

if ($conn->connect_error){
    die("Connection failed: " .$conn->connect_error);

}
echo "Connected successfully";
?>