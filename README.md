# Cloud-Computing-AWS

This is a little project based on AWS and Cloud-Computing theory. It offers a website where users can upload image and see their modified image.

The main idea is to have static webserver which stores image into a S3 and the image's status and the image's position into a database.
Then a backend server runs post-processing on the image, adds a the modified image in the S3, updates the database and send sms via SNS.

The communication between the frontend server and the backend server is done by SQS

## Achitecture
```
                ---- static webserver (Apache2)-        -----------SQS ------------                     --- SNS
               /                                 \     /                           \                   /
Load-balancer ------ static webserver ------------ ---- database + database replica --- backend server 
               \                                 /     \                           /
                ---- static webserver -----------       ----------- S3 ------------
```
