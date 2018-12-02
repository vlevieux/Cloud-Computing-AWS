<?php
session_start();
?>
<html>
	<head>
	</head>
	<body>
	<h1>Enter your AWS credentials</h1>
	<form method="POST" action="cloudwatch.php">
    		<div class="form_input">
        		<label for="mail">Aws Access Key Id</label>
        		<input type="text" id="ID" name="ID">
    		</div>
    		<div class="form_input">
        		<label for="phone">Aws Secret Access Key</label>
        		<input type="text" id="Key" name="Key">
    		</div>
    		<input class="form_input" type="submit" value="View Metrics" name="submit">
	</form>
	</body>
</html>
