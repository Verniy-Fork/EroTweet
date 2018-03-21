<?php //When called, make a request to pull a tweet from an SQL table 
	require("class/queue-database-construction.php");
	$construction = new QueueDatabaseConstruction(true);
	//row array
	$oldest = $construction->retrieveOldestEntry();
		echo "<br/>" . var_dump($oldest);
	echo "<hr/>";
	
	//ob_start();
	require("class/twitter-connection.php");
	//ob_end_clean();
	$connection = new TwitterConnection();
	$connection->makeTweet($oldest["Comment"], explode(",", $oldest["ImageLocation"]));
	
	echo "<hr/>";
	
	
	$construction->deleteOldestEntry($oldest);

	echo "Found, Added and Deleted<br/>";
?>