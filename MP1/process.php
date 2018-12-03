<?phpi
require '/home/ubuntu/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Sns\SnsClient;
use Aws\S3\Exception\S3Exception;


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
//New SNS Client
$sns = new Aws\Sns\SnsClient([
    'region' => 'us-east-1',
    'version' => 'latest'
]);
set_time_limit(0);
ignore_user_abort(1);
while(1)
{
	try {
		$queueUrl = $client->getQueueUrl([
        		'QueueName' => $queueName // REQUIRED
    		]);
    		$result = $SQS->receiveMessage(array(
        	'AttributeNames' => ['SentTimestamp'],
        	'MaxNumberOfMessages' => 1,
        	'MessageAttributeNames' => ['All'],
        	'QueueUrl' => $queueUrl, // REQUIRED
        	'WaitTimeSeconds' => 0,
    	));
    	if (count($result->get('Messages')) > 0) {
            	$filename=$result->get('Messages')[0]['Body'];
            	runimgprocessing($RDS, $S3, $filename);
            	$done=1;
    	} else {
            	echo "No messages in queue. \n";
   	}
	} catch (AwsException $e) {
    		// output error message if fails
    		error_log($e->getMessage());
	}

	if ($done==1) {

    		$donemsg = "done processing";
    		sendsms($sns,$donemsg);
    		$done = 0;
	}
	sleep(30);
}

function sendsms($sns,$message)

{
    $arn=$sns->listTopics([]);
    $arn= $arn['Topics'][0]['TopicArn'];
    $sns->publish([

        'Message' => $message, // REQUIRED

        'TopicArn' => $arn,

    ]);

}

function runimgprocessing($RDS,$S3,$sns,$filename)

{
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
                echo "Connected successfully";
        }
        catch(PDOException $e)
        {
                echo "Connection failed: " . $e->getMessage();
        }


        $sql = "SELECT * FROM Image_Processing WHERE job_status='0'";

        if ($result = $con->exec($sql)) {
                if ( $result->rowCount() > 0) {
                        $message = "Images being processed";

                        sendsms($message);

                        while ($row = $result->rowCount()) {

                                $s3_list_bucket = $S3->listBuckets([]);
                                $s3_bucket_name = $s3_list_bucket['Buckets'][0]['Name'];

                                $arn=$sns->listTopics([]);
                                $arn= $arn['Topics'][0]['TopicArn'];

                                $sub = $client->subscribe(array(
                                // TopicArn is required
                                'TopicArn' => $arn
                                // Protocol is required
                                'Protocol' => 'sms',
                                'Endpoint' => $row['phone'],
                                ));

                                //used for uploading where condition
                                $receipt = $row['uuid_receipt']

                                $s3_row_url = $row['s3_raw_url'];

                                 //calls get object function

                                getobj($s3_bucket_name, $s3_row_url, $S3);

                                //calls image manipulation and upload to s3 and update db

                                imgman($filename, $s3_bucket_name, $receipt, $RDS, $S3);
                        }

                } else {

                        echo "No records matching your query were found.";

                }

        } catch(PDOException $e)
        {
                echo "Sql request failed: " . $e->getMessage();
        }
        $conn=null;



}


function getobj($bucket_name, $filepath, $S3)
{

    try {

        // Save object to a file.

        $S3->getObject(array(

            'Bucket' => $bucket_name,

            'ObjectURL' => $s3_bucket_url,
        ));

    } catch (S3Exception $e) {

        die("error uploading" . $e);

    }

}


function imgman($filename,$receipt,$bucket_name,$RDS,$S3)

{

    $extension = explode('.', $filename);

    $postkey = current($extension);

    $extension = strtolower(end($extension));


    //name of the file with rnd name with extension

    $tmp_file_post_name = "{$postkey}-updated.{$extension}";

    $img_path_needs_process = "/home/ubuntu/vlevieux/itmd-544/MP2/{$keyname}";

    $full_file_location_and_name = "/home/ubuntu/vlevieux/itmd-544/MP2/{$tmp_file_post_name}";



    // Load the stamp and the photo to apply the watermark to

    $stamp = imagecreatefrompng('/home/ubuntu/vlevieux/itmd-544/MP2/watermark.png');

    $im = imagecreatefromjpeg($img_path_needs_process);



    // Set the margins for the stamp and get the height/width of the stamp image

    $marge_right = 10;

    $marge_bottom = 10;

    $sx = imagesx($stamp);

    $sy = imagesy($stamp);



    // Copy the stamp image onto our photo using the margin offsets and the photo

    // width to calculate positioning of the stamp.

    imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

    imagejpeg($im, $full_file_location_and_name, 100);



    //Put object in bucket

    try {

        $s3Client->putObject([

            'Bucket' => 'Bucket_name',

            'Key' => $tmp_file_post_name,

            'Body' => fopen($full_file_location_and_name, 'rb'),

            'ACL' => 'public-read'

        ]);


    } catch (S3Exception $e) {

        die("error uploading" . $e);

    }

    $result = $RDS->describeDBInstances([])
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


    //prepared statement
	
    $furl = "s3.us-east-1.amazonaws.com/" . $bucket_name . "/" . $tmp_file_post_name;

    $updatesql = "UPDATE Image_Processing set s3_raw_url " . $furl . "', status=1 WHERE receipt='" . $receipt . "'";

    try ($result2 = $conn->exec($updatesql);
    {

        echo "upload successuful";

    }  catch(PDOException $e)
    {
            echo "Update Sql request failed: " . $e->getMessage();
    }


}
?>
