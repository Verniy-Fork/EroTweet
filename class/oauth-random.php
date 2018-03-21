<?php
	class OAuthRandom{	
		public static function randomAlphaNumet($len){
			$rand_string = "";
			$opt_str = "1234567890qwertyuiopasdfghjklzxcvbnmZXCVBNMASDFGHJKLQWERTYUIOP";
			$options = str_split($opt_str);
			$max = mb_strlen($opt_str) - 1;
			for($char = 0 ; $char < $len ; $char++){
				 $rand_string .= $options[rand(0, $max)];
			}
			
			return str_replace("/", "5", str_replace("=", "2", $rand_string));
		}
	}
?>