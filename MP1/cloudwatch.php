<?php
session_start();
require '/home/ubuntu/vendor/autoload.php';
use Aws\CloudWatch\CloudWatchClient;
use Aws\Exception\AwsException;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// collect value of input field
$_SESSION["ID"] = $_POST['ID'];
$_SESSION["Key"] = $_POST['Key'];
}else{
    header('Location: login.php');
    exit;
}
$client = new CloudWatchClient([
    'region' => 'us-east-1',
    'version' => 'latest',
    'credentials' => [
        'key'    => $_SESSION["ID"],
        'secret' => $_SESSION["Key"],
    ],
]);
try {
    $result = $client->listMetrics();
	echo $result['Metrics'][1]["MetricName"].'<br />';
	$result1 = $client->getMetricStatistics(array(
        'Namespace' => 'AWS/EC2',
        'MetricName' => 'CPUUtilization',
	'Dimensions' => [['Name'=>'InstanceId',"Value" => "i-0d5e70c6f2d03423e",],],
	'StartTime' => strtotime('-1 days'),
	'EndTime' => strtotime('now'),
	'Period' => 3000,
	'Statistics' => ['Average'],
    ));
	echo count($result1["Datapoints"])."<br />";
	$count = count($result1["Datapoints"]);
    	for ($i = 0; $i < $count; $i++) {
        	echo $result1["Datapoints"][$i]["Average"]."<br />";
    	}
echo $result;
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}
?>
