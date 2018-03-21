<?php 

/*
A NOTE ON TWITTER ERRORS:
	ERROR 215 TYPICALLY MEANS YOU GOT THE AUTHENTICATION STRING FORAMT WRONG
	ERROR 32 MEANS YOU GOT THE VALUES WRONG
*/

require("class/oauth-random.php");
class TwitterConnection{
	private $oauth_data = array();
	private $media_api = "https://upload.twitter.com/1.1/media/upload.json";
	private $status_api = "https://api.twitter.com/1.1/statuses/update.json";
	
	function __construct(){
		$this->getOAuthData();
	}
	
	function getOAuthData(){
		$settings = fopen("settings/keys.ini","r");
		while(!feof($settings)){
			$line = fgets($settings);
			$key = substr($line,0,strpos($line, ":"));
					//eat last character
			$value = trim(substr($line, strpos($line, ":")+1));
			$this->oauth_data[$key] = $value;
		}
	}
	
	function makeTweet($comment, $file_arr){
		$image_string = $this->addTweetMedia($file_arr);
		
		//access info
		$request_method = "POST";

		//message info
		$encode_tweet_msg = rawurlencode($comment);
		$include_entities = "true";

		//append to postfield_string the media code via media_ids=$media_id
		$postfield_string = "include_entities=$include_entities&status=$encode_tweet_msg&media_ids=$image_string";
		$msg_len = (strlen($postfield_string));

		$random_value = OauthRandom::randomAlphaNumet(32);
		$method = "HMAC-SHA1";
		$oauth_version = "1.0";
		$timestamp = time();


						//add media id to the signature
		$signature = rawurlencode($this->generateSignature(array(
											"base_url" => $this->status_api,
											"request_method" => $request_method),
											array("include_entities" => "$include_entities",
											"status" => "$encode_tweet_msg",
											"media_ids" => "$image_string",
											"oauth_version" => "$oauth_version",
											"oauth_nonce"=>"$random_value",
											"oauth_token"=> $this->oauth_data["oauth_token"],
											"oauth_timestamp" => "$timestamp",
											"oauth_consumer_key" => $this->oauth_data["oauth_consumer_key"],
											"oauth_signature_method" => "$method"
											),
										array(
											"consumer_secret" => $this->oauth_data["consumer_secret"],
											"oauth_secret" => $this->oauth_data["oauth_secret"]
											)
		));

		$header_data = array("Accept: */*", "Connection: close","User-Agent: VerniyXYZ-CURL" ,
													"Content-Type: application/x-www-form-urlencoded;charset=UTF-8", 
													"Content-Length: $msg_len", "Host: api.twitter.com",
													
		'Authorization: OAuth oauth_consumer_key="' . $this->oauth_data["oauth_consumer_key"] .'",oauth_nonce="' . $random_value . '",oauth_signature="' .	$signature 
		. '",oauth_signature_method="' .$method . '"' . ',oauth_timestamp="' . $timestamp . '",oauth_token="' . $this->oauth_data["oauth_token"] . '",oauth_version="' . $oauth_version . '"'										
															);	
												
		//request
		echo "<hr/>";
		$curl = curl_init($this->status_api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfield_string);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		echo "<br/>-- Fin -- <hr/>";
		$content = curl_exec($curl);
		echo $content;
	}
	
	function addTweetMedia($file_arr){
		
		//image info
		$image_string = "";//delimited by ',' commas
		for($file = 0 ; $file < count($file_arr) ; $file++){
			if($file_arr[$file] != ""){
				//create data in binary/b64
				$mime_type = pathinfo($file_arr[$file], PATHINFO_EXTENSION);
				$file_arr[$file] = urldecode($file_arr[$file]);
				$binary = file_get_contents($file_arr[$file]);

				$base64 = base64_encode($binary);
				
				//upload file to twitter and get id for use in files
				$size = filesize($file_arr[$file]);
				if($file == 0)
					$image_string = $this->getMediaID($base64, $size, 'image/' . $mime_type);
				else
					$image_string .= "," . $this->getMediaID($base64, $size, 'image/' . $mime_type);		
			}
		}
		return rawurlencode($image_string);
	}
	
	function getMediaID($base64, $size, $mime_type){		
		$random_value = OAuthRandom::randomAlphaNumet(32);
		$timestamp = time();

		echo "<br/><br/>";
		/////////////MAKE INIT////////////
		//post data
		$media_id = $this->mediaInit($size, $mime_type, $random_value, $timestamp);

		echo "<br/><br/>";

		/////////////MAKE APPEND////////////
		//post data
		$this->mediaAppend($base64, $media_id, $random_value, $timestamp);
		
		echo  "<br/><br/>";

		/////////////MAKE FINAL/
		$this->makeFinal($media_id, $random_value, $timestamp);	
		echo  "<br/><br/>";
		
		return $media_id ;
	}
	
	function mediaInit($size, $mime, $random_value, $timestamp){
		$command = "INIT";
		$method = "HMAC-SHA1";
		$oauth_version = "1.0";
				
		$postfield_string = "command=$command&total_bytes=$size&media_type=$mime";
		
		$msg_len = (strlen($postfield_string));
		//header data
			  // BUILD SIGNATURE				 
			$signature =   rawurlencode($this->generateSignature(array(
										"base_url" => $this->media_api,
										"request_method" => "POST"),
										array(
										"command" => "$command",
										"total_bytes" => "$size",
										"media_type" => "$mime",
										"oauth_version" => "$oauth_version",
										"oauth_nonce"=>"$random_value",
										"oauth_token"=> $this->oauth_data["oauth_token"],
										"oauth_timestamp" => "$timestamp",
										"oauth_consumer_key" => $this->oauth_data["oauth_consumer_key"],
										"oauth_signature_method" => "$method",
										),
										array(
										"consumer_secret" => $this->oauth_data["consumer_secret"],
										"oauth_secret" => $this->oauth_data["oauth_secret"]
										)
									));
										

		
		$header_data = array("Accept: */*", "Connection: close","User-Agent: VerniyXYZ-CURL" ,"Content-Transfer-Encoding: binary",
													"Content-Type: application/x-www-form-urlencoded;charset=UTF-8", 
													"Content-Length: $msg_len", "Host: upload.twitter.com",		
		'Authorization: OAuth oauth_consumer_key="' . $this->oauth_data["oauth_consumer_key"] .'",oauth_nonce="' . $random_value . '",oauth_signature="' .
			$signature . '",oauth_signature_method="' .$method . '"' . ',oauth_timestamp="' . $timestamp . '",oauth_token="' . $this->oauth_data["oauth_token"] . '",oauth_version="' . $oauth_version . '"'										
														);			
		
		//request		
		$curl = curl_init($this->media_api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfield_string);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$media_id_arr = json_decode(curl_exec($curl), true);
		print_r ($media_id_arr);
		return $media_id_arr["media_id_string"];
}

	function mediaAppend(&$binary_file, $media_id, $random_value, $timestamp){
		$command = "APPEND";
		$method = "HMAC-SHA1";
		$oauth_version = "1.0";
		
		$segment_index = 0;
			
		//header data
			  // BUILD SIGNATURE			
		$signature =  rawurlencode($this->generateSignature(array(
									"base_url" => $this->media_api,
									"request_method" => "POST"),
									array(
									"command" => "$command",
									"media" => "$binary_file",
									"media_id"=>"$media_id",
									"segment_index"=>"$segment_index",
									"oauth_version" => "$oauth_version",
									"oauth_nonce"=>"$random_value",
									"oauth_token"=> $this->oauth_data["oauth_token"],
									"oauth_timestamp" => "$timestamp",
									"oauth_consumer_key" => $this->oauth_data["oauth_consumer_key"],
									"oauth_signature_method" => "$method",
									),
									array(
									"consumer_secret" => $this->oauth_data["consumer_secret"],
									"oauth_secret" => $this->oauth_data["oauth_secret"]
									)
								));
										

		$postfield_string = "media=" . rawurlencode($binary_file) . "&command=$command&media_id=$media_id&segment_index=$segment_index" ;
		$msg_len = (strlen($postfield_string));
		$header_data = array("Except:", "Connection: close","User-Agent: VerniyXYZ-CURL" ,"Content-Transfer-Encoding: binary",
													"Content-Type: application/x-www-form-urlencoded", 
													"Content-Length: $msg_len", "Host: upload.twitter.com",
		'Authorization: OAuth oauth_consumer_key="' . $this->oauth_data["oauth_consumer_key"] .'",oauth_nonce="' . $random_value . '",oauth_signature="' .
			$signature . '",oauth_signature_method="' .$method . '"' . ',oauth_timestamp="' . $timestamp . '",oauth_token="' . $this->oauth_data["oauth_token"] . '",oauth_version="' . $oauth_version . '"'										
														);									
		//request
		$curl = curl_init($this->media_api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfield_string);
		curl_setopt($curl, CURLOPT_HEADER  , true);  // we want headers
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$http_response = curl_exec($curl);
		echo $http_response;
	}

	function makeFinal($media_id, $random_value, $timestamp){
		$command = "FINALIZE";
		$method = "HMAC-SHA1";
		$oauth_version = "1.0";
		
		$signature =  rawurlencode($this->generateSignature(array(
								"base_url" => $this->media_api,
								"request_method" => "POST"),
								array(
								"command" => "$command",
								"media_id"=>"$media_id",
								"oauth_version" => "$oauth_version",
								"oauth_nonce"=>"$random_value",
								"oauth_token"=> $this->oauth_data["oauth_token"],
								"oauth_timestamp" => "$timestamp",
								"oauth_consumer_key" => $this->oauth_data["oauth_consumer_key"],
								"oauth_signature_method" => "$method",
								),
								array(
								"consumer_secret" => $this->oauth_data["consumer_secret"],
								"oauth_secret" => $this->oauth_data["oauth_secret"]
								)
							));
		$postfield_string = "command=$command&media_id=$media_id" ;
		$msg_len = (strlen($postfield_string));
		$header_data = array("Except:", "Connection: close","User-Agent: VerniyXYZ-CURL" ,"Content-Transfer-Encoding: binary",
													"Content-Type: application/x-www-form-urlencoded", 
													"Content-Length: $msg_len", "Host: upload.twitter.com",
		'Authorization: OAuth oauth_consumer_key="' . $this->oauth_data["oauth_consumer_key"] .'",oauth_nonce="' . $random_value . '",oauth_signature="' .
			$signature . '",oauth_signature_method="' .$method . '"' . ',oauth_timestamp="' . $timestamp . '",oauth_token="' . $this->oauth_data["oauth_token"] . '",oauth_version="' . $oauth_version . '"'										
														);									
		//request
		$curl = curl_init($this->media_api);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header_data);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfield_string);	
		curl_setopt($curl, CURLOPT_HEADER  , true);  // we want headers
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$http_response = curl_exec($curl);
		echo $http_response;			
	}

	function generateSignature($request_arr, $paramater_arr, $secret_arr){	
		  // BUILD SIGNATURE
		$request_method = strtoupper($request_arr["request_method"]);
		$base_url = rawurlencode($request_arr["base_url"]);
	
		ksort($paramater_arr);

		if(isset($paramater_arr["media"])) $paramater_arr["media"] = rawurlencode($paramater_arr["media"]);
		$paramter_string = $this->buildOAuthParamaterString($paramater_arr); 	

		$base_string = ($request_method . "&" .  $base_url  . "&" . $paramter_string);									
		$secret_string = $secret_arr["consumer_secret"] . "&" . $secret_arr["oauth_secret"];
		$signature =  (base64_encode(hash_hmac("SHA1",$base_string, $secret_string, true)));	
			
		return $signature;	
	}
	
	function buildOAuthParamaterString($paramater_arr){
		$param_string = "";
		$join_param_by_amphersand = false;
		foreach($paramater_arr as $key => $param){
			if(!$join_param_by_amphersand){
				$join_param_by_amphersand=true;
			}
			else{
				$param_string .= rawurlencode("&");
			}
			$param_string .=  rawurlencode($key . "=" . $param);
		}
		return $param_string; 		
	}
	

}
	echo"run script from externals<br/>";
?>