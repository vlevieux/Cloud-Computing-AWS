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
