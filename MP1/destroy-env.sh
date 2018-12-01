#!/bin/bash
echo "Script to destroy all Ec2 Instances and the load-balancer "

echo "Destroy the load balancer"
VAR1=$(aws elb describe-load-balancers --query 'LoadBalancerDescriptions[0].LoadBalancerName' --output=text)
echo $VAR1
aws elb delete-load-balancer --load-balancer-name $VAR1

echo " Terminate all EC2 instances"
VAR2=$(aws ec2 describe-instances --query 'Reservations[*].Instances[*].InstanceId' --output=text)
echo ${VAR2[@]}
aws ec2 terminate-instances --instance-ids ${VAR2[@]}

echo "Wait for the instances to terminate"
aws ec2 wait instance-terminated --instance-ids ${VAR2[@]}

echo "Destroy all EBS volume instances"
VAR3[0]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[0].VolumeId' --output=text)
VAR3[1]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[1].VolumeId' --output=text)
VAR3[2]=$(aws ec2 describe-volumes --filters Name=size,Values=10 --query 'Volumes[2].VolumeId' --output=text)
echo ${VAR3[@]}
aws ec2 delete-volume --volume-id ${VAR3[0]}
aws ec2 delete-volume --volume-id ${VAR3[1]}
aws ec2 delete-volume --volume-id ${VAR3[2]}

echo "Destroy Bucket"
VAR4=$(aws s3api list-buckets --query 'Buckets[].Name' --output=text)
echo $VAR4
VAR5=$(aws s3api list-objects --bucket $VAR4 --query 'Contents[].Key' --output=text)
echo $VAR5
echo "Remove all objects from S3 buckets in use"
aws s3api delete-object --bucket $VAR4 --key $VAR5
echo "Remove the empty S3 buckets"
aws s3api delete-bucket  --bucket $VAR4

echo "Destroy Database"
VAR6=$(aws rds describe-db-instances --query 'DBInstances[*].DBInstanceIdentifier[*]' --output=text)
aws rds delete-db-instance --db-instance-identifier $VAR6 --skip-final-snapshot

echo "Destroy SQS Topic"
#VAR6=$(aws sqs --get-queue-url --queue-name inclass-sqs-topic)
VAR7=$(aws sqs list-queues --queue-name-prefix inclass --query 'QueueUrls[*]' --output=text)
aws sqs delete-queue --queue-url $VAR7

echo "Destroy SNS Topic"
VAR8=$(aws sns list-topics --query 'Topics[*]' --output=text)
aws sns delete-topic --topic-arn $VAR8

