#!/bin/bash
sudo apt-get update
sudo apt-get install apache2 -y
su - ubuntu -l -c "git clone git@github.com:illinoistech-itm/vlevieux.git"
sudo cp /home/ubuntu/vlevieux/itmd-544/MP1/* /var/www/html/

sudo mkfs -t ext4 /dev/xvdh
sudo mkdir /mnt/datadisk
sudo mount /dev/xvdh /mnt/datadisk
