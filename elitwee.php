<?php /*
Name: Elitwee
Description: Object-oriented PHP classes for accessing the Twitter API and caching the JSON data.
Author: Calvin Freitas
Version: 1.0
Author URI: http://calvinfreitas.com/elitwee/
License:  Creative Commons Attribution-Share Alike 3.0 Unported License
Warranties: None
Last Modified: August 19, 2008

Requirements: PHP 5.2.
Tested on: PHP 5.2.4
*/

define('ELITWEE_VERSION','1.3');
define('ELITWEE_RELEASE_DATE','August 30, 2008');
define('ELITWEE_URL','http://calvinfreitas.com/elitwee/');
define('ELITWEE_AUTHOR_NAME','Calvin Freitas');
define('ELITWEE_AUTHOR_URL','http://calvinfreitas.com/');
define('ELITWEE_AUTHOR_EMAIL','elitwee@calvinfreitas.com');

class ElitweeCache {
	public $name; 		// cache location is determined by request URL
	public $filename; 	//filename the cache will be stored in
	public $cache_location; //file directory to store cache
	public $cache_time; 	//amount of time to store in cache before refresh (seconds)
	
	public function __construct($name,$cache_location,$cache_time) {
		$this->name = (string) $name;
		$this->cache_location = (string) $cache_location;
		$this->cache_time = (int) $cache_time;
		$this->filename = $this->get_filename();
	}
	
	public function write_cache($data) {
		if(is_writable($this->cache_location)) {
			file_put_contents($this->filename,$data); // data should be the JSON string (not decoded)
		}
		else {throw new Exception('Cache location must be writable.');}
	}
	
	private function get_filename() {
		return $this->cache_location . 'elitwee_' . md5($this->name) . '.json_cache';
	}
	
	public function cache_length() {
		if (file_exists($this->filename)) {
			$length = time()-filemtime($this->filename);
			return $length;
		}
		else {return null;}
	}
	
	public function is_expired() {
		$length = $this->cache_length();
		if ($length == null) {return true;}
		return ($length < $this->cache_time) ? false : true;
	}
	
	public function retrieve_cache() {
		if (file_exists($this->filename)) {
			$length = $this->cache_length();
			if (!isset($length)) {return null;}
			if ($length < $this->cache_time) {
				//cache is not expired, grab the data from file and return
				return file_get_contents($this->filename);
			}
			else {
				//cache data is expired
				return null;
			}
		}
	}
}

class Elitwee {
	private $service;	// service name
	public  $username;	// username
	public  $password;	// password -- required for some API calls (posting, friend_timeline, etc.)
	public  $max_count;	// maximum tweet count for object
	private $tweets;	// Tweet object array
	private $count; 	// iterator for $tweets array
	private $cache_location;
	private $cache_time;
	
	private $post_format;	//either xml or json
	private $user_timeline_format; //xml, json, rss, or atom
	
	private $user_search_term;

// The constructor
	public function __construct($username, $password, $max_count) {
		// Set-up the class variables from the parameters.
		$this->service = 'Twitter'; //for now, only Twitter is supported
		
		$this->username 	= (string) $username; // It's good practice to use type-casting.
		$this->password 	= (string) $password;
		$this->max_count 	= (int)    $max_count;
		
		$this->tweets = array(); // this will be an array of JSON returned from the query
		$this->public_tweets = array();
		
		$this->post_format = 'xml'; //post format defaults to xml
		$this->user_timeline_format = 'xml';
		
		$this->user_search_term = '';
		
		$this->cache_location = dirname(__FILE__) . '/cache/'; // default cache location
		$this->cache_time = 300; //defaults cache time (in seconds)
	}
	
	function __destruct() {
		//nothing to do here yet
	}
	
	public function is_auth() {
		if (isset($this->username) && isset($this->password)) {return true;} else{return false;}
	}
	
	public function home_url() {                     
		//eventually check services other than Twitter but for now
		return 'http://www.twitter.com/' . $this->username;
	}
	
	//function to grab Twitter URL (xml or json)
	//example addresss: http://twitter.com/statuses/user_timeline/calvinf.rss?count=5
	public function get_feed_url() {
		$feed_url = 'http://www.twitter.com/statuses/user_timeline/' . $this->username . '.' . $this->user_timeline_format;
		if (isset($this->max_count)) {$feed_url .= "?count=" . $this->max_count;}
		return $feed_url;
	}
	
	//function to grab search.twitter RSS URL
	public function get_search_feed_url($searchterm) {
		return 'http://search.twitter.com/search.' . $this->user_timeline_format . '?q=' . urlencode($searchterm);
	}
	
	public function get_channel_url() {
		//return URL for browsing service's channels 
		//a la a tweet w/ #gnomedex should point to http://search.twitter.com/search?q=%23gnomedex
		if ($this->service == 'Twitter') {
			return 'http://search.twitter.com/search?q=%23'; // %23 is # symbol urlencoded
		}
		//add other services later
	}
	
	public function tweet_count() {
		return count($this->tweets);
	}
	
	public function set_cache_time($cache_time) {
		$this->cache_time = $cache_time;
	}
	
	public function cache_time() {return $this->cache_time;}
	
	public function set_cache_location($location) {
		$this->cache_location = $location;
	}
	
	public function cache_location() {return $this->cache_location;}
	
	public function set_post_format($format) {
		$format = strtolower($format); // lowercase so XML or JSON will work, too
		switch ($format) {
		case "xml":
		case "json":
			$this->post_format = $format;
			break;
		default:
			throw new Exception('Post format must be xml or json.');
		}
	}
	public function get_post_format() {
		return $this->post_format;
	}
	
	public function set_user_search_term($searchterm){
		$this->user_search_term = $searchterm;
	}
	
	public function set_user_timeline_format($format) {
		//JSON is supported now, support for other formats will be added later
		
		$format = strtolower($format); // lowercase
		switch ($format) {
		case "xml":
		case "json":
		case "rss":
		case "atom":
			$this->user_timeline_format = $format;
			break;
		default:
			throw new Exception('User timeline format must be xml or json.');
		}
	}
	
	public function get_user_timeline_format() {
		return $this->user_timeline_format;
	}
	
	public function authenticated() {
		$url = 'http://twitter.com/account/verify_credentials.json';
		try {
			$result = $this->call($url,null);
		}
		catch (Exception $e) { //if error, unable to authenticate
			return false;
		}
		if(isset($result)) {
			$array = json_decode($result);
			if(isset($array)) {return $array->authorized;} //authorized is bool via twitter api
		}
		return false;
	}
	
	public function post($message) {
		if ($message) {
			$message = (string) $message;
		}
		else {
			throw new Exception('Error: Cannot post empty message to ' . $this->service);
			return;
		}
		
		$url = 'http://twitter.com/statuses/update.' . $this->post_format;
		
		try {$result = $this->call($url,$message);}
		catch (Exception $e) {
			throw $e;
		}
	}
	
	public function get_search_results($count=false,$since=false,$since_id=false,$page=false) {
		$url = $this->get_search_feed_url($this->user_search_term);
		
		//only one of the four options should be set
		if ($count) {$url .= '?count=' . urlencode($count);}
		elseif ($since) {$url .='?since=' . urlencode($since);}
		elseif ($since_id) {$url .= '?since_id=' . urlencode($since_id);}
		elseif ($page) {$url .= '?page=' . urlencode($page);}
		
		$result = $this->check_cache($url);
		
		if(isset($result)) {
			$array = json_decode($result);
			
			if(is_array($array->results)) {return $array->results;} //return tweets if available
		}
		
		return null; //if unable to retrieve tweets, return null
	}
	
	public function get_user_timeline($count=false,$since=false,$since_id=false,$page=false) {
		$url = $this->get_feed_url();
		
		//only one of the four options should be set
		if ($count) {$url .= '?count=' . urlencode($count);}
		elseif ($since) {$url .='?since=' . urlencode($since);}
		elseif ($since_id) {$url .= '?since_id=' . urlencode($since_id);}
		elseif ($page) {$url .= '?page=' . urlencode($page);}
		
		$result = $this->check_cache($url);
		
		if(isset($result)) {
			$array = json_decode($result);
			if(is_array($array)) {return $array;} //return tweets if available
		}
		return null; //if unable to retrieve tweets, return null
	}
	
	public function get_public_timeline() {
		$url = 'http://twitter.com/statuses/public_timeline.json';
		
		$result = $this->check_cache($url);
		
		if(isset($result)) {
			$array = json_decode($result);
			if(is_array($array)) {return $array;}
		}
		return null;
	}
	
	public function get_friend_timeline() {
		if ($this->username == null) {
			throw new Exception('Must provide a username and password in order to authenticate and access friend timeline.');
		}
		elseif ($this->password == null) {
			throw new Exception('Must provide a password for the user in order to authenticate and access friend timeline information.');
		}
		
		$url = 'http://twitter.com/statuses/friends_timeline.json';
		
		$result = $this->check_cache($url);
		
		if(isset($result)) {
			$array = json_decode($result);
			if(is_array($array)) {return $array;}
		}
		return null;
	}
	
	private function call($url, $message) {
		$session = curl_init($url);
		
		curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
		
		if($this->username !== false && $this->password !== false) {
			curl_setopt($session, CURLOPT_USERPWD, $this->username . ":" . $this->password);
		}
		
		if ($message != null) {
			curl_setopt($session, CURLOPT_POST, 1);
			curl_setopt($session, CURLOPT_POSTFIELDS,"status=" . $message . "&source=elitwee");
		}
		
		$result = curl_exec($session);
		
		$resultArray = curl_getinfo($session);
		$http_code = $resultArray['http_code'];
		
		if ($http_code != '200') {throw new Exception('Unable to access ' . $this->service . ' at URL (' . $url .'). Verify service status. (HTTP code ' . $http_code  . '.)'); return null;} 
		
		curl_close($session);
		return $result;
	}
	
	private function check_cache($url) {
		$cache_name = $url . "|" . $this->username; //create unique but retrievable name for storing cache
		$json_cache = new ElitweeCache($cache_name,$this->cache_location(),$this->cache_time);
		
		if ($json_cache->is_expired() == false) {
			//cache isn't expired, retrieve cache
			return $json_cache->retrieve_cache();
		}
		else {
			//cache is expired, make API call, retrieve results
			$result = $this->call($url, null);
			$json_cache->write_cache($result); //write fresh data to cache
			return $result;
		}
	}
	
	//extra functions for tweet formatting
	public function format($tweet) {
		$tweet = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<i><a href=\"\\0\">\\0</a></i>",$tweet); // turn any URL's into links
		$tweet = ereg_replace("@([a-zA-Z0-9_]+)([^a-zA-Z0-9_])",'<a href="http://www.twitter.com/\\1">@\\1</a>\\2',$tweet); // add "@username" links
		$tweet = ereg_replace("#([a-zA-Z0-9]+)",'<a href="' . $this->get_channel_url() . '\\1">#\\1</a>',$tweet);// convert any # symbols to links to the channel (i.e. on twitter it'll point to twitter search for #whatever)
		return $tweet;
	}
}



function relative_time($original,$ante) {//original time, text to proceed approx date/time
	$diff = time() - $original; //time difference in seconds
	
	//if greater than 24 hours, return pretty date instead of relative time
	if ($diff > (60 * 60 * 24)) {return strftime('%B %d, %Y %H:%M:%S',$original);}
	elseif ($diff > (60*60)) { //greater than an hour
		$count = floor($diff / (60*60));
		return ($count == 1) ? $ante . "1 hour ago" : $ante . $count . " hours ago";
	}
	elseif ($diff > 60) {
		$count = floor($diff / (60));
		return ($count == 1) ? "1 minute ago" : "$count minutes ago";
	}
	else {return ($diff == 1) ? "1 second ago" : "$diff seconds ago";}
}

?>
