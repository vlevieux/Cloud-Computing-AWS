#!/bin/bash
DB_ID_READ=replica-db

echo "====DESTROY MP3===="

echo -n "Delete auto scaling group... "
aws autoscaling delete-auto-scaling-group \
        --auto-scaling-group-name basic-asg \
        --force-delete \
        &> /dev/null
echo "Done."

echo -n "Delete launch configuration... "
aws autoscaling delete-launch-configuration \
        --launch-configuration-name basic-launch-configuration \
        &> /dev/null
echo "Done."

echo -n "Delete db instance $DB_ID_READ..."
aws rds delete-db-instance \
        --db-instance-identifier $DB_ID_READ \
        --skip-final-snapshot \
        &> /dev/null
echo "Done."

echo "=====Finished====="
