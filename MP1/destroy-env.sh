#!/bin/bash
INSTANCE_IDS=$(aws ec2 describe-instances --filters 'Name=instance-state-name,Values=running' --query 'Reservations[*].Instances[*].InstanceId' --output=text)
echo "Instances found : $INSTANCE_IDS"
aws ec2 terminate-instances --instance-ids $INSTANCE_IDS
echo "Instances are terminating, please wait"
aws ec2 wait instance-terminated --instance-ids $INSTANCE_IDS
echo "All instances are terminated"
LOAD_BALANCER=$(aws elb describe-load-balancers --query 'LoadBalancerDescriptions[0].LoadBalancerName')
echo "Load balancer found : $LOAD_BALANCER"
aws elb delete-load-balancer --load-balancer-name "load-balancer-test"
echo "Deleting load balancer"
DELETE_VOLUME=$(aws ec2 describe-volumes --filters 'Name=size,Values=10' --query "Volumes[*].VolumeId" --output=text)
echo "Volume found $DELETE_VOLUME"
for ID in ${DELETE_VOLUME[@]};
do
	aws ec2 delete-volume --volume-id $ID
done
echo "All volumes are deleted"
echo "Done!"
