<html>
<head></head>
<body>
<br/>
<a href="http://verniy.xyz/twitter/queue-form">Back to Form</a>
<br/><hr/>

<div style="margin:10%">

<?php
	require("class/queue-database-construction.php");

	$construction = new QueueDatabaseConstruction(true);

	$comment = $construction->checkCommentValid($_POST["comment"]);

	$file_string = $construction->uploadAndVerify($_FILES);

	echo "<hr/>";

	$do_not_submit = false;
	for($file = 0 ; $file < 4 ; $file++) if($construction->die_state[$file] == true) $do_not_submit = true;
	if($comment_error) $do_not_submit = true;

	if($do_not_submit) echo "Error in Tweet. Aborting addition to queue.<br/>";
	else $construction->addToDatabase($file_string, $comment);

	$construction->displayTabularDatabase();

?>

</div>
<a href="http://verniy.xyz/twitter/queue-form">Back to Form</a>
</body>
</html>
