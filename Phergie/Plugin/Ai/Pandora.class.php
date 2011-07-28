<?php
class PandoraBots {

	private $botid = 'a225a2e01e36e26b';
	public $pipe; // This will be our cURL handle in a bit
	protected $timout = 50;
	protected $default_response = "Probably"; // Use the function to set this.
	private $path_url = "http://www.pandorabots.com/pandora/talk?botid="; // So we can easily change url if needed

	public function __construct(){
		$botid = $this->botid;
		if(isset($botid)){
			// Init Curl
			$this->pipe = curl_init();
			curl_setopt($this->pipe, CURLOPT_URL, $this->path_url.$botid);
			curl_setopt($this->pipe, CURLOPT_POST, 1);
			curl_setopt($this->pipe, CURLOPT_RETURNTRANSFER, 1);
		}			
	}
	
	public function setKey($key){
		if(isset($key)){
			$this->botid = trim($key);		
		}
	}	

	public function default_response($responce=""){
		/* Check to see if $responce is set otherwise we return the default */
		if(isset($responce)){
			// Check to make sure new responce is actually there
			if(!$this->sanitize($responce) == FALSE){
				$this->default_responce = $this->sanitize($responce); // Set Responce
			}
		} else {
			// No new responce set, return the already set one.
			return $this->default_responce;
		}
	}

	public function say($user_input){
		$name = "input"; // Used to submit the form post
		$input = $this->sanitize($user_input);
		curl_setopt($this->pipe, CURLOPT_TIMEOUT, $this->timout);
		curl_setopt ($this->pipe, CURLOPT_POSTFIELDS, "Name=$name&input=$input");
		curl_setopt ($this->pipe, CURLOPT_FOLLOWLOCATION, 1);
		$reply = curl_exec($this->pipe);
		if(isset($reply)){
			return $this->get_reply($reply);
		}
		curl_close($this->pipe);
	}

	private function set_timeout($int){
		if(!is_int($int)){
			$this->timeout = 60;
			return FALSE;
		} else {
			$this->timeout = $int;
			return TRUE;
		}
	}

	private function sanitize($string){
		$string = stripslashes(trim(htmlentities(stripslashes($string))));
			if(!empty($string)){
				return $string;
			} else { // Nothing is returned, return false
				return FALSE;
			}
	}

	private function get_reply($input, $tag='font'){
		// Do a little regex to get the bot reply
		$pattern = "#<$tag color=\"\w+\">(.*?)</$tag>#";
		$var = preg_match($pattern, $input, $matches);
		$result = $this->sanitize($matches[1]);
		/* Simple Sanity Check  - Null */
			if($result == FALSE OR empty($result)){
				return $this->default_response();
			} else {
				return $result; // Return valid string.
			}
	}

}

