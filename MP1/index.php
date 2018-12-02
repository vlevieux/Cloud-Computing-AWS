<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Welcome Page</title>
</head>

<body>
	<h1>Image Upload</h1>

	<p>Please enter your information to upload the image</p>
	<form method="POST" action="upload.php" enctype="multipart/form-data">
		Name: <br />
		<input type="text" name="user_name"/><br />
		Email: <br />
		<input type="text" name="user_email"/><br />
		Phone number: <br />
		<input type="text" name="user_phone" /><br />
		<p>
		Image: <br />
		<p>
		<input type="file" name="user_image" id="user_image">
		<label for="fileToUpload"> Select an image to upload</label><br/>
		<p>
        	<input type="submit" name="submit" value="Upload Image">
	</form>
</body>
</html>
