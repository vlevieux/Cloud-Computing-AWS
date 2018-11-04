#!/bin/bash
ELB_NAME="load-balancer-test"
AVAILABILITY_ZONE="us-east-1a"
COUNT="3"
IMAGE_ID="ami-01c178ad2a57b96cb"
SECURITY_GROUP="sg-0e929342eb43226a6"
KEY_NAME="devenv-key"
S3_BUCKET_NAME="bucket-name"

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

INSTANCE_IDS=$(aws ec2 run-instances --image-id $IMAGE_ID --security-group-ids $SECURITY_GROUP --count $COUNT --instance-type t2.micro --key-name $KEY_NAME --user-data file://create-app.sh --query 'Instances[*].InstanceId' --output=text)
INSTANCE_IDS_ARRAY=($INSTANCE_IDS)

for INDEX in `seq 1 $COUNT`;
do
	aws ec2 create-volume --size 10 --availability-zone $AVAILABILITY_ZONE
done

VOLUME_IDS=($(aws ec2 describe-volumes --filters "Name=size,Values=10" --query "Volumes[*].VolumeId" --output=text))

aws ec2 wait instance-running --instance-ids ${INSTANCE_IDS}

let "COUNT--"
for INDEX in `seq 0 $COUNT`;
do
	aws ec2 attach-volume --volume-id ${VOLUME_IDS[INDEX]} --instance-id ${INSTANCE_IDS_ARRAY[INDEX]} --device /dev/xvdh
done

aws elb create-load-balancer --load-balancer-name $ELB_NAME --listeners "Protocol=HTTP,LoadBalancerPort=80,InstanceProtocol=HTTP,InstancePort=80" --availability-zone $AVAILABILITY_ZONE

aws elb create-lb-cookie-stickiness-policy --load-balancer-name $ELB_NAME --policy-name myPolicy

aws elb register-instances-with-load-balancer --load-balancer-name $ELB_NAME --instances $INSTANCE_IDS
