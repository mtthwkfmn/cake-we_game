<?php
/**
 * WeGame DataSource
 *
 * A datasource that hooks up to the WeGame API and fetches XML data using an HTTP Request.
 *
 * @author      Miles Johnson - www.milesj.me
 * @copyright   Copyright 2006-2010, Miles Johnson, Inc.
 * @license     http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link        http://milesj.me/resources/script/wegame-datasource
 * @link        http://wegame.com, http://api.wegame.com
 */

App::import('Core', array('HttpSocket', 'Xml', 'Folder'));

class WeGameSource extends DataSource {

	/**
	 * Current version: www.milesj.me/resources/script/wegame-datasource
	 *
	 * @access public
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * The URL for the API.
	 *
	 * @var string
	 */
	const API_URL = 'http://api.wegame.com';

	/**
	 * Set the cache settings.
	 *
	 * @access public
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		parent::__construct($config);

		if (Cache::config('weGame') === false) {
			$cachePath = CACHE .'we_game'. DS;

			// Create the cache dir
			if (!file_exists($cachePath)) {
				$this->Folder = new Folder();
				$this->Folder->create($cachePath, 0777);
			}

			Cache::config('weGame', array(
				'engine' 	=> 'File',
				'serialize' => true,
				'prefix'	=> '',
				'path' 		=> $cachePath,
				'duration'	=> '+1 day'
			));
		}
	}

	/**
	 * Describe the data source. Nothing really to put here.
	 *
	 * @access public
	 * @param object $Model
	 * @return string
	 */
	public function describe($Model) {
		return self::API_URL;
	}

	/**
	 * List out all the sources, aka the API URL.
	 *
	 * @access public
	 * @return string
	 */
	public function listSource() {
		return self::API_URL;
	}

	/**
	 * Read / fetch data from the API and format the response before returning.
	 *
	 * @access public
	 * @param string $Model
	 * @param array $query
	 * @return array
	 */
	public function read($Model, array $query = array()) {
		$cache = true;
		$cacheTime = '+1 day';
		$cacheKey = md5(serialize($query['conditions']));

		// Get configuration and remove
		if (isset($query['conditions']['url'])) {
			$url = $query['conditions']['url'];
			unset($query['conditions']['url']);
		} else {
			return 'DATASOURCE_URL';
		}

		if (isset($query['conditions']['id'])) {
			$url .= $query['conditions']['id'] .'/';
			unset($query['conditions']['id']);
		}

		if (isset($query['conditions']['cache'])) {
			if (is_bool($query['conditions']['cache'])) {
				$cache = $query['conditions']['cache'];
			} else if (is_string($query['conditions']['cache'])) {
				$cacheTime = $query['conditions']['cache'];
			}
			unset($query['conditions']['cache']);
		}

		// Find cached first
		$doCache = ($cache == true && Configure::write('Cache.disable') == false);
		
		if ($doCache) {
			Cache::set(array('duration' => $cacheTime));
			$results = Cache::read($cacheKey, 'weGame');

			if (is_array($results)) {
				return $results;
			}
		}

		$this->Http = new HttpSocket();
		$response = $this->Http->get(self::API_URL . $url, array_merge($query['conditions'], array('api_key' => $this->config['apiKey'])));

		if (substr($response, 0, 5) == '<?xml') {
			$xml = new Xml($response);
			$xml = $xml->toArray();

			if ($doCache) {
				Cache::set(array('duration' => $cacheTime));
				Cache::write($cacheKey, $xml, 'weGame');
			}

			return $xml;
		} else {
			return $response;
		}
	}

}