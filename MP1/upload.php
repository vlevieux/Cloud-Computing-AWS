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

echo $_POST['user_name'];
echo "\n";
echo $_POST['user_email'];
echo "\n";
echo $_POST['user_phone'];
echo "\n";

if (isset($_FILES['user_image'])) {
	$aExtraInfo = getimagesize($_FILES['user_image']['tmp_name']);
	$sImage = "data:" . $aExtraInfo["mime"] . ";base64," . base64_encode(file_get_contents($_FILES['user_image']['tmp_name']));
	echo '<p>The image has been uploaded successfully</p><p>Preview:</p><img src="'.$sImage.'" alt="Your Image" />';
	$fileName = $_FILES['user_image']['name'];
	$s3_raw_url = sendToBucket($fileName, $sImage, $S3);
	sendToSQS($fileName, $SQS);
	//publish to a topic
        /*$result = $sns->publish([
		 'Message' => 'Items have been uploaded', 
                 'TopicArn' => 'arn:aws:sns:us-east-1:964874203517:inclass-sns-topic',
	]);*/
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
	    $result = $client->sendMessage($params);
	    var_dump($result);
	} catch (AwsException $e) {
	    // output error message if fails
	    error_log($e->getMessage());
	}
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

        echo "<br />The object URL is: ";
        $s3_raw_url = $s3_object['ObjectURL'];
	return $s3_raw_url;
}

function openConnection() {
        $servername = "vlevieuxdb.cv18wjykhbpt.us-east-1.rds.amazonaws.com";
        $username = "victor";
        $password = "test1234";
	$dbname = "dbvlevieux";
        // Create connection
        $conn = new mysqli($servername, $username, $password,$dbname);
        // Check connection
        if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
        }
        echo "Connection successful";

        return $conn;
}

function closeConnection($conn) {
        $conn->close();
}

?>
