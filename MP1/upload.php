<?php

require '/home/ubuntu/vendor/autoload.php';
use Aws\S3\S3Client;
use  Aws\Sqs\SqsClient;
use Aws\Rds\RdsClient;

//New S3 Client
$S3 = new Aws\S3\S3Client([
   'version' => 'latest',
   'region' => 'us-west-2'
]);
// New SQS Client
$SQS = new Aws\Sqs\SqsClient([
   'version' => 'latest',
   'region'  => 'us-east-1'
]);
// New RDS Client
$RDS = new Aws\Rds\RdsClient([
    'version' => 'latest',
    'region' => 'us-east-1'
]);

$user_name = $_POST['user_name'];
$user_email = $_POST['user_email'];
$user_phone = $_POST['user_phone'];

if (isset($_FILES['user_image'])) {
	$aExtraInfo = getimagesize($_FILES['user_image']['tmp_name']);
	$sImage = "data:" . $aExtraInfo["mime"] . ";base64," . base64_encode(file_get_contents($_FILES['user_image']['tmp_name']));
	$fileName = $_FILES['user_image']['name'];
	$s3_raw_url = sendToBucket($fileName, $sImage, $S3);
	$receipt_handle = sendToSQS($fileName, $SQS);
	insertIntoDB($receipt_handle, $user_name, $user_email, $user_phone, $s3_raw_url, $RDS);
	include "upload.html";
}

function sendToSQS($fileName, $SQS){
	$list_result = $SQS->listQueues([]);
	$sqs_url = $list_result['QueueUrls'][0];
	$params = [
	    'DelaySeconds' => 10,
	    'MessageBody' => $fileName,
	    'QueueUrl' => $sqs_url
	];

	try {
		$result = $SQS->sendMessage($params);
		$receipt_handle = $result['MessageId'];
	} catch (AwsException $e) {
	    // output error message if fails
	    error_log($e->getMessage());
	}
	return $receipt_handle;
}

function sendToBucket($fileName, $sImage, $S3) {
	$s3_list_bucket = $S3->listBuckets([]);
        $s3_bucket_name = $s3_list_bucket['Buckets'][0]['Name'];
        $rawImage = base64_decode(end(explode(",", $sImage)));
        $s3_object = $S3->putObject([
                'ACL' => 'public-read',
                'Bucket' => $s3_bucket_name,
                'Key' => $fileName,
                'Body' =>  $rawImage,
        ]);

        $s3_raw_url = $s3_object['ObjectURL'];
	return $s3_raw_url;
}

function insertIntoDB($receipt_handle, $user_name, $user_email, $user_phone, $s3_raw_url, $RDS) {
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
		include "upload-fail.html";
		exit();
    	}
	$sql = "INSERT INTO Image_Processing(uuid_receipt,username,email,phone, s3_raw_url,job_status) VALUES ('$receipt_handle', '$user_name', '$user_email', '$user_phone', '$s3_raw_url','0')";
	$conn->exec($sql);
	$conn=null;
}

?>
