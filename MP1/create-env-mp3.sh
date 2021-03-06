#!/bin/bash
AMI="ami-01c178ad2a57b96cb"
LOAD_BALANCER_NAME="load-balancer-test"
KEY_NAME="devenv-key"
SECURITY_GROUP="sg-0e929342eb43226a6"
IAM_PROFILE="basic-role"
AVAILABILITY_ZONE="us-east-1a"
DB_ID_READ="replica-db"
DB_ID_WRITE="vlevieuxdb"

while [ -n "$1" ]
do
	  case "$1" in
		"--image-id")
			AMI="$2"
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
		"--db-replica-id")
			DB_ID_READ="$2"
			shift 2
			;;
		"--db-instance_id")
			DB_ID_WRITE="$2"
			shift 2
			;;
		"--availability_zone")
			AVAILABILITY_ZONE="$2"
			shift 2
			;;
		"--elb-name")
			LOAD_BALANCER_NAME="$2"
			shift 2
			;;
		"--iam-profile")
			IAM_PROFILE="$2"
			shift 2
			;;
		*)
			echo "$1 is not a valid argument."
			exit
			;;
  esac
done

echo "====MP3===="

echo "Create launch-configuration... "
aws autoscaling create-launch-configuration \
        --launch-configuration-name basic-launch-configuration \
        --image-id $AMI \
        --key-name $KEY_NAME \
        --security-groups $SECURITY_GROUP \
        --instance-type t2.micro \
        --iam-instance-profile $IAM_PROFILE \
        --user-data file://create-app-frontend.sh \
        --block-device-mappings "[{\"DeviceName\": \"/dev/xvdh\",\"Ebs\":{\"VolumeSize\":10}}]"
echo "Done."

echo "Create auto scaling group... "
aws autoscaling create-auto-scaling-group \
        --auto-scaling-group-name basic-asg \
        --launch-configuration-name basic-launch-configuration \
        --load-balancer-names $LOAD_BALANCER_NAME \
        --min-size 2 \
        --max-size 4 \
        --desired-capacity 3 \
        --health-check-type ELB \
        --health-check-grace-period 120 \
        --availability-zones $AVAILABILITY_ZONE
echo "Done."

echo "Create db instance ... "
aws rds create-db-instance-read-replica \
        --db-instance-identifier $DB_ID_READ \
        --source-db-instance-identifier $DB_ID_WRITE \
        --availability-zone $AVAILABILITY_ZONE
echo "Done."

echo "Waiting db instance available... "
aws rds wait db-instance-available --db-instance-identifier $DB_ID_READ
echo "Done."

echo "====Finished===="
