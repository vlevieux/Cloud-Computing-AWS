<?php
require '/home/ubuntu/vendor/autoload.php';
use Aws\S3\S3Client;
use  Aws\Sqs\SqsClient;
use Aws\Rds\RdsClient;
// New RDS Client
$RDS = new Aws\Rds\RdsClient([
    'version' => 'latest',
    'region' => 'us-east-1'
]);
$header = "<!DOCTYPE html><html><head><title>Gallery</title></head><body><h1>Gallery</h1>";
$footer = "</body></html>";
echo $header;
readDB($RDS);
echo $footer;
function readDB($RDS) {
	$result = $RDS->describeDBInstances([]);
	$servername = $result['DBInstances'][0]['Endpoint']['Address'];
	$username = $result['DBInstances'][0]['MasterUsername'];
	$dbname = $result['DBInstances'][0]['DBName'];
	$password = "test1234";
        $dsn="mysql:host={$servername};port=3306;dbname={$dbname}";
	try {
    		$conn = new PDO($dsn, $username, $password);
    		// set the PDO error mode to exception
    		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  	}
	catch(PDOException $e)
    	{
		echo "Connection failed: " . $e->getMessage();
    	}
	$sql = $conn->prepare("SELECT email, s3_raw_url, s3_finished_url from Image_Processing");
	$sql->execute();
	while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
  		$email = $row['email'];
  		$s3_raw_url = $row['s3_raw_url'];
  		$s3_finished_url = $row['s3_finished_url'];
		printImage($email,$s3_raw_url,$s3_finished_url);
	}
	$conn=null;
}
function printImage($email, $s3_raw_url, $s3_finished_url) {
	echo "<div>";
	echo "<p>Image posted by ".$email."</p>";
	echo "<img src=".$s3_raw_url." />";
	echo "<img src=".$s3_finished_url." />";
	echo "</div>";
}
?>
