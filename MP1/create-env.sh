#!/bin/bash
ELB_NAME="load-balancer-test"
AVAILABILITY_ZONE="us-east-1a"
COUNT="3"
IMAGE_ID="ami-01c178ad2a57b96cb"
SECURITY_GROUP="sg-0e929342eb43226a6"
KEY_NAME="devenv-key"
S3_BUCKET_NAME="vlevieuxmp21"
IAM_PROFILE="basic-role"
DB_ID="vlevieuxdb"
DB_USERNAME="victor"
DB_PASSWORD="test1234"

while [ -n "$1" ]
do
	  case "$1" in
		"--image-id")
			IMAGE_ID="$2"
			shift 2
			;;
		"--key-name")
			KEY_NAME="$2"
			shift 2
			;;
		"--security-group")
			SECURITY_GROUP="$2"
			shift 2
			;;
		"--count")
			COUNT="$2"
			shift 2
			;;
		"--elb-name")
			ELB_NAME="$2"
			shift 2
			;;
		"--s3-bucket-name")
			S3_BUCKET_NAME="$2"
			shift 2
			;;
		*)
			echo "$1 is not a valid argument."
			exit
			;;
  esac
done
echo "$IMAGE_ID $KEY_NAME $SECURITY_GROUP $COUNT $ELB_NAME $S3_BUCKET_NAME"

echo "Creating instance profile..."
aws iam create-instance-profile --instance-profile-name instance-full-access
echo "Adding role to instance profile..."
aws iam add-role-to-instance-profile --instance-profile-name instance-full-access --role-name $IAM_PROFILE
echo "Done."

echo "====FRONTEND===="
echo "Creating instances..."
INSTANCE_IDS=$(aws ec2 run-instances --image-id $IMAGE_ID --security-group-ids $SECURITY_GROUP --count $COUNT --instance-type t2.micro --key-name $KEY_NAME --user-data file://create-app-frontend.sh --query 'Instances[*].InstanceId' --output=text)
INSTANCE_IDS_ARRAY=($INSTANCE_IDS)

echo "Creating volumes..."
for INDEX in `seq 1 $COUNT`;
do
	aws ec2 create-volume --size 10 --availability-zone $AVAILABILITY_ZONE
done
VOLUME_ID[0]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[0].VolumeId')
VOLUME_ID[1]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[1].VolumeId')
VOLUME_ID[2]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[2].VolumeId')
echo "Waiting for instance running..."
aws ec2 wait instance-running --instance-ids ${INSTANCE_IDS}

echo "Attaching volume..."
for (( I=0; I<$COUNT; I++))
do
   aws ec2 attach-volume --device /dev/xvdh --instance-id ${INSTANCE_IDS_ARRAY[$I]} --volume-id ${VOLUME_ID[$I]}
done
echo "Done."

echo "====BACKEND===="
aws ec2 run-instances --image-id $IMAGE_ID --count 1  --instance-type t2.micro --key-name $KEY_NAME --security-groups-ids $SECURITY_GROUP --iam-instance-profile Name=instance-full-access --user-data file://create-app-backend.sh
echo "Done."

echo "====LoadBalancer===="
echo "Creating load balancer..."
aws elb create-load-balancer --load-balancer-name $ELB_NAME --listeners "Protocol=HTTP,LoadBalancerPort=80,InstanceProtocol=HTTP,InstancePort=80" --availability-zone $AVAILABILITY_ZONE
echo "LoadBalancer : HTTP:80"

echo "Creating cookie stickiness policy..."
aws elb create-lb-cookie-stickiness-policy --load-balancer-name $ELB_NAME --policy-name myPolicy

echo "Registering instances with load balancer..."
aws elb register-instances-with-load-balancer --load-balancer-name $ELB_NAME --instances $INSTANCE_IDS
echo "Done."

echo "====S3 Bucket===="
aws s3api create-bucket --bucket $S3_BUCKET_NAME --create-bucket-configuration LocationConstraint=us-west-2
echo "Waiting the bucket"
aws s3api wait bucket-exists --bucket $S3_BUCKET_NAME
aws s3api put-bucket-acl --bucket $S3_BUCKET_NAME --acl public-read
echo "Done."

echo "====RDS Database===="
aws rds create-db-instance --db-name dbvlevieux --allocated-storage 10 --db-instance-class db.m1.small --db-instance-identifier $DB_ID --engine mysql --master-username $DB_USERNAME --master-user-password $DB_PASSWORD --availability-zone $AVAILABILITY_ZONE
echo "Waiting Database..."
aws rds wait db-instance-available --db-instance-identifier $DB_ID
SERVER_NAME=$(aws rds describe-db-instances --db-instance-identifier $DB_ID --query 'DBInstances[0].Endpoint.Address')
echo "Initialize the database..."
php db-init.php $SERVER_NAME $DB_USERNAME $DB_PASSWORD dbvlevieux
echo "Done."

echo "====SQS Topic===="
aws sqs create-queue --queue-name inclass-sqs-queue
echo "Done."

echo "====SNS Topic===="
aws sns create-topic --name inclass-sns-topic
echo "Done."

echo "Finished."
