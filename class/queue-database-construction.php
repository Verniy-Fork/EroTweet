<?php

class QueueDatabaseConstruction{
	
	private $sql_data = array();
	private $connection = null;
	
	public $die_state = array();
	public $comment_error = false;
	public $delete_status = false;
	
	function __construct($connect = false){
		$sql_ini = fopen("settings/sql.ini", "r");
		while(!feof($sql_ini)){
			$line = fgets($sql_ini);
			$key = substr($line, 0, strpos($line, ":"));
			$value = trim(substr($line, strpos($line, ":")+1));
			$this->sql_data[$key] = $value;
		}
		if($connect == true) $this->connectToDatabase();
	}
	
	function connectToDatabase(){
		$this->connection = new mysqli($this->sql_data["connection"], $this->sql_data["user"],
									$this->sql_data["pass"], $this->sql_data["database"]);
		if (!$this->connection) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}
	}
	
	function buildQueueForm(){
		echo'<form action="add-to-queue.php" enctype="multipart/form-data" method="POST" target="_self">
			<label>Comment:</label><br />
			<textarea id="Comment" name="comment" rows="10" cols="60">';
			
		echo '
<Comment String>
Artist: @<Artist>
@HentaiAdvisor @Hentai_Retweet @DoujinsApp @waifu_trash @HentaiTeengirl @Hentai_Babess
<Specific Tagging>
#hentai  #hentaicommunity #nsfw  #lewd #porn #animeleft #hibiki #verniy
&&Me On The Left&&';
			
		echo '</textarea>
			<p id="CharacterCount"></p>

			<input name="MAX_FILE_SIZE" type="hidden" value="5242880" />
			<input name="file1" type="file" id="f1" /><input name="file2" type="file" id="f2" /><br/>
			<input name="file3" type="file" id="f3" /><input name="file4" type="file" id="f4" /><br/>
			<hr />
			<p id="errorMsg">Input a comment and/or file</p>
			<input id="submit" type="submit" /></form>
		';
	}
	
	function buildPassForm(){
		echo"<form action='' method='POST'>
		<input name='name'><br/>
		<input name='pass' type='password'><br/>
		<input type='submit' id='authorization-input' value='Authorize'></form>";
	}
	
	function checkCommentValid($tweet_comment){
		$COMMENT_MAX = 500;

		if(mb_strlen($tweet_comment) > $COMMENT_MAX){
			echo "Comment too long[Server]<br/>";
			$this->comment_error = true;
			return "";
		}
		$this->comment_error = false;
		return $tweet_comment;
	}
	
	function uploadAndVerify($files){								
		$FILE_MAX = 5242880;
		$file_arr = array();
		$file_string = "";
		$first = true;
		for($file = 1; $file <= 4; $file++){
			$upload_location = "images/" . basename($files["file" . (string)$file]["name"]);
			if($files["file" . (string)$file]["error"] == 0 && $upload_location !== "images/" && $files["file" . (string)$file]["size"] < $FILE_MAX){
				$file_arr[$file - 1] = $upload_location;
				if($first){
					$file_string .= rawurlencode($upload_location);
					$first = false;
				}
				else{
					$file_string .=  "," . rawurlencode($upload_location);
				}
				if (move_uploaded_file($files["file" . (string)$file]["tmp_name"], $upload_location )) {
					echo "File: $file  was valid.<br/>";
				} 
				else {
					echo "File: $file_location " . " Detected an error <br/>";
					$file_arr[$file - 1] = "0";
					$die_state[$file - 1] = true;
					continue;
				}
				$die_state[$file - 1] = false;
			}
			else{
				$file_arr[$file - 1] = 0;
				if($files["file" . (string)$file]["size"] >= $FILE_MAX){
					echo "file" . (string)$file ." Over filesize limit-Server<br/>";
					$this->die_state[$file - 1] = true;
				}
				else if($files["file" . (string)$file]["error"] == 1){
					echo "file $file, PHP err " . $files["file" . (string)$file]["error"] . " <br/>";
					$this->die_state[$file - 1] = true;
				}
				else if($files["file" . (string)$file]["error"] == 2){
					echo "file $file, Over size limit-Client<br/>";
					$this->die_state[$file - 1] = true;
				}
				else if($files["file" . (string)$file]["error"] == 3){
					echo "file $file, The uploaded file was only partially uploaded. <br/>";
					$this->die_state[$file - 1] = true;
				}
				else if($files["file" . (string)$file]["error"] == 4){
					echo "file $file, Empty<br/>";
					$this->die_state[$file - 1] = false;
				}
				else{
					echo "file $file, Unkown Upload Error " . $files["file" . (string)$file]["error"] . "<br/>";	
					$this->die_state[$file - 1] = true;
				}
			}
		}
		return $file_string;
		var_dump($file_arr);
	}
	
	function addToDatabase($file_string, $comment){
		if($file_string == "" AND $comment == ""){
			echo "Empty form<br/>";
				return; 
		} 
		$insert_query = $this->connection->prepare("INSERT INTO TweetQueue(PostNo,Comment,ImageLocation) VALUES ('',?,?)");
		$file_path = $file_string;
		if (!$insert_query->bind_param("ss", $comment, $file_path)){
			echo "Prepared Statement Error<br/>";

		}

		if (!$insert_query->execute()){
			echo "Execution Error " . $insert_query->errno . "  " . $insert_query->error;
		}
		else{
			echo "Added to post queue<br/>";
		}
	}

	function displayTabularDatabase(){
		echo "<br/>Displaying All entries(lower number means posted sooner): <br/>";
		$result = $this->connection->query("Select * from TweetQueue ORDER BY PostNo DESC;");

		echo "<table border='1'>";
		for($row = $result->num_rows - 1; $row >= 0 ; $row--){
			echo"<tr>";
			$tupple = $result->fetch_row();
			foreach($tupple as $col){
				echo "<td>$col</td>";
			}
			echo"</tr>";
		}
		echo "</table><hr/>";
	}

	function retrieveOldestEntry(){
		$retrieval_query = "SELECT * FROM TweetQueue ORDER BY PostNo ASC LIMIT 1";

		$most_recent = $this->connection->query($retrieval_query);
		print_r($most_recent); 
		echo "\n";

		$data_arr = $most_recent->fetch_assoc();

		print_r($data_arr);

		$file_arr  = explode(",", rawurldecode($data_arr["ImageLocation"]));
			
		echo "Comm: " . $data_arr["Comment"] . " - ILoc: ";
		print_r($file_arr);
		return $data_arr;
	}
	
	function deleteOldestEntry($oldest){
		echo $oldest;
		$delete_querry = $this->connection->prepare("DELETE FROM TweetQueue WHERE PostNo=?;");
		$delete_querry->bind_param("s", $oldest["PostNo"]);
		$this->delete_status = $delete_querry->execute();
		
		if($this->delete_status !== 1){
			echo "<pre><hr/>Delete Err" . $delete_query->error;
		}
	}
}

?>