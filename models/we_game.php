<?php
/**
 * WeGame Model
 *
 * Base model that augments the WeGame datasource and defines all the methods available for the API.
 * Refer to the official API for documented parameters: http://api.wegame.com/docs
 *
 * @author 		Miles Johnson - www.milesj.me
 * @copyright	Copyright 2006-2009, Miles Johnson, Inc.
 * @license 	http://www.opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		www.milesj.me/resources/script/wegame-datasource
 * @link		www.wegame.com, api.wegame.com
 */

class WeGame extends AppModel {

	/**
	 * No database table needed.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useTable = false;

	/**
	 * Use the weGame datasource.
	 *
	 * @access public
	 * @var boolean
	 */
	public $useDbConfig = 'weGame';

	/**
	 * Fetch a single games data, based on id|slug|title.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function game(array $conditions = array()) {
		return $this->__request(array('url' => '/games/single/', 'id' => ''), $conditions, 'Game');
	}

	/**
	 * Fetch all games within a certain timeframe.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function games(array $conditions = array()) {
		return $this->__request(array('url' => '/games/query/', 'created' => strtotime('-3 months')), $conditions, 'Game');
	}

	/**
	 * Fetch all games that are supported on the client; within a certain timeframe.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function supportedGames(array $conditions = array()) {
		return $this->__request(array('url' => '/games/supported/', 'created' => strtotime('-3 months')), $conditions, 'Game');
	}

	/**
	 * Fetch a single videos data, based on id|slug|title.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function video(array $conditions = array()) {
		return $this->__request(array('url' => '/videos/single/', 'id' => ''), $conditions, 'Video');
	}

	/**
	 * Fetch all videos based on multiple filtering criteria.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function videos(array $conditions = array()) {
		return $this->__request(array('url' => '/videos/query/', 'search' => '', 'offset' => '', 'sort' => 'views', 'tag' => '', 'created' => strtotime('-1 month')), $conditions, 'Video');
	}

	/**
	 * Fetch a single screenshots data, based on id|slug|title.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function screenshot(array $conditions = array()) {
		return $this->__request(array('url' => '/screenshots/single/', 'id' => ''), $conditions, 'Screenshot');
	}

	/**
	 * Fetch all screenshots based on multiple filtering criteria.
	 *
	 * @access public
	 * @param array $conditions
	 * @return array
	 */
	public function screenshots(array $conditions = array()) {
		return $this->__request(array('url' => '/screenshots/query/', 'search' => '', 'offset' => '', 'sort' => 'views', 'tag' => '', 'created' => strtotime('-1 month')), $conditions, 'Screenshot');
	}

	/**
	 * Merge the default conditions with the user defined ones. Once prepared, dispatch the HTTP request to the API.
	 * Will format and return the correct data, and or error string.
	 *
	 * @access private
	 * @param array $defaults
	 * @param array $conditions
	 * @param string $type
	 * @return string|array
	 */
	private function __request($defaults, $conditions, $type) {
		$conditions = array_filter(array_intersect_key(array_merge($defaults, $conditions), $defaults));

		if (!isset($conditions['cache'])) {
			$conditions['cache'] = true;
		}

		if (isset($conditions['sort']) && !in_array($conditions['sort'], array('likes', 'comments', 'featured', 'views', 'date', 'game'))) {
			$conditions['sort'] = 'views';
		}

		$results = $this->find('all', array('conditions' => $conditions));
		
		if (is_array($results)) {
			if (isset($results['Results'][$type])) {
				return $results['Results'][$type];

			} else if (isset($results['Response']['Error'])) {
				return $results['Response']['Error'];

			} else {
				return $results;
			}
		} else {
			switch ((string)$results) {
				case 'INVALID_GAME':
					return 'Invalid game was specified in a query or the game does not exist.';
				break;
				case 'DUPLICATE_GAMES_FOUND':
					return 'The query matched multiple games.';
				break;
				case 'MISSING_API_KEY':
					return 'You are trying to query a page that requires an API Key.';
				break;
				case 'INVALID_API_KEY':
					return 'You are trying to query a page with an invalid API key.';
				break;
				case 'DATASOURCE_URL':
					return 'The URL for the datasource is either missing or incorrect.';
				break;
				default:
					return 'An unexpected error has occurred. Please contact WeGame.com with the API issue.';
				break;
			}
		}
	}

}