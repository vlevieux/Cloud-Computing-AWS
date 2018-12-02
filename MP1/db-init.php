<?php

$servername=$argv[1];
$username=$argv[2];
$password=$argv[3];
$dbname=$argv[4];

$dsn="mysql:host={$servername};port=3306;dbname={$dbname}";
try {
    $conn = new PDO($dsn, $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully"; 
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }

$sql = "CREATE TABLE IF NOT EXISTS Image_Processing(id INT(6) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, uuid_receipt VARCHAR(30), username VARCHAR(30) NOT NULL, email VARCHAR(200), phone VARCHAR(20), s3_raw_url VARCHAR(255) NOT NULL, s3_finished_url VARCHAR(255) NOT NULL, job_status INT(6), dt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
$conn->exec($sql);

$conn=null;
?>
