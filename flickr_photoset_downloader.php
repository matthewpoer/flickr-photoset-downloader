<?php
class flickr_photoset_downloader{

	/**
	 * the Photoset ID
	 */
	private $_setid;

	/**
	 * The Flickr API key
	 */
	private $_api_key = '29e1ef5030d211e2dd2813572d947f8e';

	/**
	 * full list of the highest-available quality photos
	 */
	private $_hq_url_list = array();

	/**
	 * init
	 * 
	 * @param $url to the photoset
	 * @param $api_key to use with the Flickr API
	 */
	public function __construct($url,$api_key=null){
		if($api_key) $this->_api_key = $api_key;
		$this->get_setid_from_url($url);
		$this->retrieve_set_photo_list();
		$this->download_photos();
		echo "Completed\n";
	}

	/**
	 * parse the URL to find the Photoset's ID
	 * 
	 * @param $url
	 * @throws Exception when URL is empty or malformed
	 * @throws Exception when expected set format is not found
	 */
	private function get_setid_from_url($url){
		// sanity check
		if(empty($url) || !parse_url($url)){
			throw new Exception("URL is missing or malformed");
		}

		// drop trailing slash if exists
		if($url[strlen($url)-1] == '/'){
			$url = substr($url,0,-1);
		}

		// split URL into parts
		$a = explode('/',$url);

		// convert last section to int for validation
		$setid = intval($a[count($a)-1]);

		// validation... all digits and length of 17
		if(is_int($setid) && strlen($setid) == 17){
			$this->_setid = $setid;
			echo "URL parsing successful\n";
			return;
		}

		throw new Exception('URL could not be parsed correctly');
	}
	
	/**
	 * cycle through photo set and build list of photo URLs
	 * 
	 * @todo support 500+ image sets that require pagination
	 */
	private function retrieve_set_photo_list(){
		$e = flickr_api_wrapper::call(array(
			'method' => 'flickr.photosets.getPhotos',
			'api_key' => $this->_api_key,
			'photoset_id' => $this->_setid,
		));
		foreach($e['photoset']['photo'] as $photo){
			$this->_hq_url_list[] = $this->find_hq_link($photo['id']);
		}
		echo "Photos found in set: " . count($this->_hq_url_list) . "\n";
	}

	/**
	 * iterate the photo size data and find the HQ-est photo's URL
	 * 
	 * @param $photoid string ID of the individual photo we want to capture
	 * @return string URL of the highest-available quality of said photo
	 */
	private function find_hq_link($photoid){
		$e = flickr_api_wrapper::call(array(
			'method' => 'flickr.photos.getSizes',
			'api_key' => $this->_api_key,
			'photo_id' => $photoid,
		));
		$short = $e['sizes']['size'];
		$key = count($e['sizes']['size'])-1;
		return $short[$key]['source'];
	}

	/**
	 * Iterate through the HQ Download URLs and download each one. 
	 * Count off every 5 downloads and on the last file. 
	 */
	private function download_photos(){
		$i = 1;
		$t = count($this->_hq_url_list);

		foreach($this->_hq_url_list as $url){
			$fp = fopen(basename($url), 'w+');

			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
			curl_setopt($ch, CURLOPT_FILE , $fp);
			$data = curl_exec($ch);

			curl_close($ch);
			fclose($fp);
			if($i%5==0 || $i==$t){
				echo "Downloaded {$i}\n";
			}
			$i++;
		}
	}
}

class flickr_api_wrapper{
	/**
	 * Build the URL for an API call and use cURL to return the appropriate 
	 * data. Does not support any sort of authentication, designed for fetching
	 * information only from public sets or photos. 
	 * 
	 * @param $p array (associative) of API parameters, like this: 
	 * array(
	 *   'method' => '',
	 *   'api_key' => '',
	 *   'format' => '',
	 *   'photoset_id' => '',
	 * )
	 * 
	 * Parameters method and api_key are required. If format is not specified 
	 * then json will be used. At least one additional parameter is required. 
	 * 
	 * @throws Exception
	 * @return mixed string, array, bool depending on API method
	 * 
	 * @author Matthew Poer (github.com/matthewpoer) <matthewpoer@gmail.com>
	 */
	public static function call(Array $p){

		$url = 'http://api.flickr.com/services/rest/';

		if(empty($p['method'])){
			throw new Exception('missing API method');
		}else{
			$url .= '?method=' . $p['method'];
			unset($p['method']);
		}

		if(empty($p['api_key'])){
			throw new Exception('missing API key');
		}else{
			$url .= '&api_key=' . $p['api_key'];
			unset($p['api_key']);
		}

		if(empty($p['format'])){
			$url .= '&format=php_serial';
		}else{
			$url .= '&format=' . $p['format'];
			unset($p['format']);
		}

		if(empty($p)){
			throw new Exception('missing additional parameters');
		}

		foreach($p as $key => $value){
			$url .= '&' . $key . '=' . $value;
		}

		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);

		$data = unserialize($data);

		if(isset($data['stat']) && $data['stat'] == 'fail'){
			throw new Exception("Flickr API error code: {$data['code']}. Message: {$data['message']}");
		}

		return $data;
	}
}

/** 
 * Launch Controls...
 */
$controls = <<<EOT
flickr_photoset_downloader is intended to be a standalone CLI application. Use
like this:

php -f flickr_photoset_downloader.php http://www.flickr.com/photos/someuser/sets/01234567890123456/

or like this: 

php -f flickr_photoset_downloader.php http://www.flickr.com/photos/someuser/sets/01234567890123456/ 291_example_32_char_api_key_7f8e

where the first parameter is the URL to the photo set, and the second optional 
parameter is the is the API Key you'd prefer to use. If you do not specify an 
API key, the default will be used, 29e1ef5030d211e2dd2813572d947f8e, which is 
registered and tracked at by Matthew Poer and Flickr at 
http://www.flickr.com/services/apps/72157636529341406/


EOT;
if(empty($argv) || empty($argv[1]) || count($argv) > 3 || count($argv) < 2){
	echo $controls;
}else{
	$url = $argv[1];
	$apikey = empty($argv[2]) ? '' : $argv[2];
	$a = new flickr_photoset_downloader($url,$apikey);
}
