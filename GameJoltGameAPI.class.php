<?php
/**
 * GameJoltTrophyAPI
 * Ported straight from the Java API into PHP.
 * 
 * @version 0.9
 * @Author: Ashley Gwinnell
 * @Copyright: Ashley Gwinnell
 * @Project: Framework
 * @Year: 2010
 */
class GameJoltGameAPI
{
	private $protocol = "http://";
	private $api_root = "gamejolt.com/api/game/";
	
	private $game_id;
	private $private_key;
	private $version;
	
	private $username;
	private $usertoken;
	
	private $verbose = false;
	private $verified = false;
	private $using_curl = false;
	
	/**
	 * Create a new GameJoltAPI with out verifiying the user. 
	 * You should call verifyUser(username, usertoken) to verify the user.
	 * @param game_id Your Game's Unique ID.
	 * @param private_key Your Game's Unique (Private) Key.
	 */
	public function __construct($game_id, $private_key)
	{
		$this->game_id = $game_id;
		$this->private_key = $private_key;
		$this->version = 1;
	}
	
	/**
	 * Set whether the script should use cURL. It does not use cURL by default.
	 * @param bool Whether the script should use cURL or not.
	 */
	public function setUsingCURL($bool) {
		$this->using_curl = $bool;
	}
	
	/**
	 * Set the version of the GameJolt API to use.
	 * @param version The version of the GameJolt API to be using.
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
	
	/**
	 * Get the version of the GameJolt API you are using. 
	 * Current API Version is 1.
	 * @return The API version in use.
	 */
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * Check whether the user/player has verified their credentials.
	 * @return whether the user/player has verified their credentials or not.
	 */
	public function isVerified() {
		return $this->verified;
	}
	
	/**
	 * Sets whether the API should print out debug information to the Console.
	 * By default, this is set to true.
	 * @param b whether the API should print out debug informationto the Console.
	 */
	public function setVerbose($b) {
		$this->verbose = $b;
	}
	
	/**
	 * Give the currently verified user a trophy specified by ID.
	 * This method uses the trophy's ID.
	 * @param trophy_id The ID of Trophy to give.
	 * @return true on successfully given trophy.
	 */
	public function achieveTrophy($trophy_id) {
		$response = "";
		$response = $this->request("trophies/add-achieved", "trophy_id=" . $trophy_id);
		if (strpos($response, "success:\"true\"") !== FALSE) {
			return true;
		} else {
			if ($this->verbose) {
				echo "GameJoltAPI: Could not give Trophy to user.<br/>\n";
				echo $response . "<br/>";
			}
			return false;
		}
	}
	
	/**
	 * Get a list of trophies filtered with the Achieved parameter.
	 * The parameter can be "TRUE" for achieved trophies, "FALSE" for 
	 * unachieved trophies or "EMPTY" for all trophies.
	 * @param a The type of trophies to get.
	 * @return A list of trophy objects.
	 */
	public function getTrophies($type) {
		$trophies = array();
		$response = $this->request("trophies/", "achieved=" . strtolower($type));
		
		$lines = explode("\n", $response);
		$t = new Trophy();
		for ($i = 1; $i < count($lines); $i++) {
			$key = substr($lines[$i], 0, strpos($lines[$i], ":")); // from start until colon.
			$value = substr($lines[$i], strpos($lines[$i], ":")+2, strrpos($lines[$i], '"')); // after colon and inverted comma until last inverted comma
			if ($key == "id") {
				$t = new Trophy();
			}
			$t->addProperty($key, $value);
			if ($key == "achieved") {
				$trophies[] = $t;
			}
		}
		return $trophies;
	}
	
	/**
	 * Gets a single trophy from GameJolt as specified by trophyId
	 * @param trophyId The ID of the Trophy you want to get.
	 * @return The Trophy Object with the ID passed.
	 */
	public function getTrophy($trophy_id) {
		$response = $this->request("trophies/", "trophy_id=" . $trophy_id);
		if (strpos($response, "success:\"true\"") === FALSE) {
			if ($this->verbose) { echo "GameJoltAPI: Could not get Trophy with Id " . $trophyId . ".<br/>"; }
			return null;
		}
		$lines = explode("\n", $response);
		print_r($lines);
		echo $lines[1], strpos('"', $lines[1])+1;
		$t = new Trophy();
		$t->addProperty("id", 			substr($lines[1], strpos($lines[1], '"') + 1, 			strrpos($lines[1], '"') - strpos($lines[1], '"') - 1));
		$t->addProperty("title", 		substr($lines[2], strpos($lines[2], '"')+1, 			strrpos($lines[2], '"') - strpos($lines[2], '"') - 1));
		$t->addProperty("description", 	substr($lines[3], strpos($lines[3], '"')+1, 			strrpos($lines[3], '"') - strpos($lines[3], '"') - 1));
		$t->addProperty("difficulty", 	strtoupper(substr($lines[4], strpos($lines[4], '"')+1, 	strrpos($lines[4], '"') - strpos($lines[4], '"') - 1)));
		$t->addProperty("image_url", 	substr($lines[5], strpos($lines[5], '"')+1, 			strrpos($lines[5], '"') - strpos($lines[5], '"') - 1));
		$t->addProperty("achieved", 	substr($lines[6], strpos($lines[6], '"')+1, 			strrpos($lines[6], '"') - strpos($lines[6], '"') - 1));
		return $t;
	}
	
	public function getVerifiedUser() {
		if ($this->verified == false) {
			return new GJUser();
		}
		$response = $this->request("users/", "username=" . $this->username);
		if (strpos($response, "success:\"true\"") === FALSE) {
			if ($this->verbose) { echo "GameJoltGameAPI: Could not get User with User " . $this->username . ".<br/>"; }
			return null;
		}
		$lines = explode("\n", $response);
		$user = new GJUser();
		for ($i = 1; $i < count($lines); $i++) {
			$key = substr($lines[$i], 0, strpos($lines[$i], ":")); // from start until colon.
			$value = substr($lines[$i], strpos($lines[$i], ":")+2, strrpos($lines[$i], '"')); // after colon and inverted comma until last inverted comma
			$user->addProperty($key, substr($value,0, strlen($value)-2));
		}
		$user->addProperty("token", $this->usertoken);
		return $user;
	}
	
	/**
	 * Attempt to verify the Players Credentials.
	 * @param username The Player's Username.
	 * @param userToken The Player's User Token.
	 * @return true if the User was successfully verified, false otherwise.
	 */
	public function verifyUser($username, $usertoken) {
		$this->verified = false;
		$params = array();
		$params['username'] = $username;
		$params['user_token'] = $usertoken;    	
    	$response = $this->requestFromArray("users/auth/", $params, false);
    	if ($this->verbose) { echo "Response from verifyUser(): " . $response . "<br/>"; }
    	$lines = explode("\n", $response);
    	foreach($lines as $key => $line) {
			$ls = explode(":", $line);
			$ls2 = strlen($ls[1]) - 2;
			$r = substr($ls[1], 1, 4);
			if ($r == "true") {
    			$this->username = $username;
    			$this->usertoken = $usertoken;
    			$this->verified = true;
    			return true;
    		}
    	}
    	return false;
	}
	
	/**
	 * Perform a GameJolt API request.
	 * Use this one if you know your HTTP requests.
	 * @param method The API method to call. Note that gamejolt.com/api/game/ is already prepended.
	 * @param paramsLine The GET request params, such as "trophy_id=23&achieved=empty".
	 * @return The response, default is keypair.
	 */
	public function request($method, $paramsline) {
		$array = array();
		$params = explode("&", $paramsline);
		for ($i = 0; $i < count($params); $i++) {
			if (strlen($params[$i]) == 0) {
				continue;
			}
			$s = explode("=", $params[$i]);
			$key = $s[0];
			$value = $s[1];
			$array[$key] = $value;
		}
		return $this->requestFromArray($method, $array, true);
	}
	
	/**
	 * Make a request to the GameJolt API.
	 * @param method The GameJolt API method, such as "add-trophy", without the "game-api/" part.
	 * @param params A map of the parameters you want to include. 
	 * 				 Note that if the user is verified you do not have to include the username/user_token/game_id.
	 * @param requireVerified This is only set to false when checking if the user is verified.
	 * @return
	 */
	public function requestFromArray($method, $params, $require_verified) {
		if ($requireVerified && !$this->verified) {
			return "REQUIRES_AUTHENTICATION";
		}
		
		if (!$this->verified) {
			$user_token = $params['user_token'];
			$params['user_token'] = $user_token . $this->private_key;
			$urlString = $this->getRequestURL($method, $params);
			$signature = md5($urlString);
			$params['user_token'] = $user_token;
			$params['signature']  = $signature;
		} else {
			$params['user_token'] = $this->usertoken . $this->private_key;
			$params['username'] = $this->username;
			$urlString = $this->getRequestURL($method, $params);
			$signature = md5($urlString);
			
			$params['user_token'] = $this->usertoken;			
			$params['signature'] = $signature;
		}
		
		$urlString = $this->getRequestURL($method, $params);
		if ($this->verbose) { echo "urlString: " . $urlString  . "<br/>"; }
		return $this->openURLAndGetResponse($urlString);
	}
	
	/**
	 * Performs the HTTP Request using either CURL or file functions.
	 * @param urlString The URL to HTTP Request.
	 * @return The HTTP Response.
	 */
	private function openURLAndGetResponse($url) {
		$str = "";
		if ($this->using_curl) {
			$str = curl_get_contents($url);
		} else { 
			$str = file_get_contents($url);
		}
		return $str;
	}
	
	/**
	 * Get the full request url from the parameters given.
	 * @param method The GameJolt API method, such as "game-api/add-trophy".
	 * @param params A map of the parameters you want to include. 
	 * @return The full request url.
	 */
	private function getRequestURL($method, $params) {
		$urlString = $this->protocol . $this->api_root . "v" . $this->version . "/" . $method . "?game_id=" . $this->game_id;
		$user_token = "";
		foreach($params as $key => $value) {
			if ($key == "user_token") {
				$user_token .= $value;
				continue;
			}
			$urlString .= "&" . $key . "=" . $value;
		}
		$urlString .= "&user_token=" . $user_token;
		return $urlString;
	}
	
	function curl_get_contents($url){
        $crl = curl_init();
        $timeout = 5;
        curl_setopt ($crl, CURLOPT_URL,$url);
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $ret = curl_exec($crl);
        curl_close($crl);
        return $ret;
	}
}



/**
 * User
 * Ported straight from the Java API into PHP.
 * 
 * @author Ashley Gwinnell
 * @version 0.9
 */
class GJUser 
{
	/** The User properties */
	private $properties;
	
	/**
	 * Create a new User.
	 */
	public function __construct() {
		$this->properties = array();
	}
	
	/**
	 * Adds a property to the User.
	 * @param key The key by which the property can be accessed.
	 * @param value The value for the key.
	 */
	public function addProperty($key, $value) {
		$this->properties[$key] = $value;
	}
	
	/**
	 * Gets a property of the User that isn't specified by a specific method.
	 * This exists for forward compatibility.
	 * @param key The key of the User attribute you want to obtain.
	 * @return A property of the User that isn't specified by a specific method.
	 */
	public function getProperty($key) {
		return $this->properties[$key];
	}
	
	/**
	 * Get the ID of the User.
	 * @return The ID of the Trophy.
	 */
	public function getId() {
		return $this->getProperty("id");
	}
	
	/**
	 * Get the Username of the User.
	 * @return The ID of the Trophy.
	 */
	public function getUsername() {
		return $this->getProperty("username");
	}
	
	/**
	 * Get the Username of the User.
	 * @return The ID of the Trophy.
	 */
	public function getToken() {
		return $this->getProperty("token");
	}
	
	
	/**
	 * Get the Username of the User.
	 * @return The ID of the Trophy.
	 */
	public function getAvatarURL() {
		return $this->getProperty("avatar_url");
	}
	
	public function toString() {
		return "{id=" . $this->getId() . ", title=" . $this->getTitle() . "}";
	}
}

/**
 * Trophy is an achievement in the GameJolt API.
 * Ported straight from the Java API into PHP.
 * 
 * @author Ashley Gwinnell
 * @version 0.9
 */
class Trophy 
{

	/** The Trophy properties */
	private $properties;
	
	/**
	 * Create a new Trophy.
	 */
	public function __construct() {
		$this->properties = array();
	}
	
	/**
	 * Adds a property to the Trophy.
	 * @param key The key by which the property can be accessed.
	 * @param value The value for the key.
	 */
	public function addProperty($key, $value) {
		$this->properties[$key] = $value;
	}
	
	/**
	 * Gets a property of the Trophy that isn't specified by a specific method.
	 * This exists for forward compatibility.
	 * @param key The key of the Trophy attribute you want to obtain.
	 * @return A property of the Trophy that isn't specified by a specific method.
	 */
	public function getProperty($key) {
		return $this->properties[$key];
	}
	
	/**
	 * Get the ID of the Trophy.
	 * @return The ID of the Trophy.
	 */
	public function getId() {
		return $this->getProperty("id");
	}
	/**
	 * Get the name of the Trophy.
	 * @return The name of the Trophy.
	 */
	public function getTitle() {
		return $this->getProperty("title");
	}
	
	/**
	 * Get the description of the Trophy.
	 * @return The description of the Trophy.
	 */
	public function getDescription() {
		return $this->getProperty("description");
	}
	
	/**
	 * Get the difficulty of the Trophy. 
	 * i.e. Bronze, Silver, Gold, Platinum.
	 * @return The difficulty of the Trophy.
	 */
	public function getDifficulty() {
		return $this->getProperty("difficulty");
	}
	
	/**
	 * Determines whether the Trophy is achieved or not.
	 * @return True if the verified user has the Trophy.
	 */
	public function isAchieved() {
		return (bool) $this->getProperty("achieved");
	}
	
	/**
	 * Gets the URL of the Trophy's image.
	 * @return The URL of the Trophy's image.
	 */
	public function getImageURL() {
		return $this->getProperty("image_url");
	}
	
	public function toString() {
		return "{id=" . $this->getId() . ", title=" . $this->getTitle() . "}";
	}
}