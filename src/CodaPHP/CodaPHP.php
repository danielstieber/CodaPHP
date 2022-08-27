<?php
namespace CodaPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * CodaPHP Library
 * 
 * CodaPHP is a library that makes it easy to use data from Coda (https://www.coda.io)
 * docs your in web projects by using the Coda API (https://coda.io/developers/apis/v1).
 * Use on your own risk.
 * 
 * This file is licensed under the MIT license.
 */
class CodaPHP
{
	/**
	 * The API Token, the guzzle client and cache settings
	 * @var string $apiToken
	 * @var obj $client
	 * @var bool $cacheData if true, tries to cache requests as local files
	 * @var int $maxAge expiry time of a cachefile in seconds
	 */
	protected $apiToken, $client, $cacheData, $maxAge;

	/**
	 * Base URL of the Coda Api
	 */
	const API_BASE = 'https://coda.io/apis/v1';
	const CACHE_DIR = '.codaphp_cache' . DIRECTORY_SEPARATOR;
	/**
	 * Creates new guzzle client with authentication and 
	 */
	public function __construct($apiToken, $cacheData = false, $maxAge = 604800)
	{
		$this->apiToken = $apiToken;
		$this->client = new Client([
			'headers' => [
				'Authorization' => 'Bearer '.$this->apiToken
			]
		]);
		if($cacheData && (is_dir(self::CACHE_DIR) || mkdir(self::CACHE_DIR))) {
			// caching is only available when the cache folder exists / can be created
			$this->cacheData = $cacheData;
		} else {
			$this->cacheData = false;
		}
		$this->maxAge = $maxAge;
	}
	/**
	 * Performs a request using guzzle
	 * 
	 * @param string $url
	 * @param array $params Guzzle request params
	 * @param string $method HTTP request method
	 * @param bool $addstatus When true, the return will include the HTTP status code
	 * @param bool $ignoreCache When true, the caching option will be ignored
	 * @return array
	 */
	protected function request($url, array $params = [], $method = 'GET', $addStatus = false, $ignoreCache = false)
	{
		$cacheFile = self::CACHE_DIR . md5(json_encode([$url => $params])) . '.json';
		if($method == 'GET' && $ignoreCache === false && $this->cacheData === true && file_exists($cacheFile)) { // checks for cached response
			$cache = json_decode(file_get_contents($cacheFile), true);
			if((time() - $cache[0]) <= $this->maxAge) {
				$httpCode = $cache[1];
				$dataArray = $cache[2];
				if($addStatus) {
					return ['statusCode' => $httpCode, 'result' => $dataArray];
				} else {
					return $dataArray;
				}
			} else {
				unlink($cacheFile);
			}
		}

		try {
			$response = $this->client->request($method, self::API_BASE . $url, $params);
		} catch (BadResponseException $e) {
			$errorContent = $e->getResponse()->getBody()->getContents();
			return(json_decode($errorContent, true));
		}

		$httpCode = $response->getStatusCode();
		$responseString = $response->getBody()->getContents();
		$dataArray = json_decode($responseString, true);

		if($method == 'GET' && $ignoreCache === false && $this->cacheData === true) { // caches response
			$cache = [time(), $httpCode, $dataArray];
			$filecreation = file_put_contents($cacheFile, json_encode($cache));
		}

		if (is_array($dataArray) && JSON_ERROR_NONE === json_last_error()) {
			if($addStatus) {
				return ['statusCode' => $httpCode, 'result' => $dataArray];
			} else {
				return $dataArray;
			}
		}
		throw new \UnexpectedValueException('Invalid JSON: ' . $responseString);
	}
	/**
	 * Returns the Coda doc id of a Coda doc url 
	 * 
	 * @param string $url Coda doc url
	 * @return string 10-digit Coda doc id
	 */
	public static function getDocId($url) {
		$re = '/coda.io\/d\/.*?_d(.{10})\/*/m';
		preg_match($re, $url, $res);
		return $res[1] ?? false;
	}
	/**
	 * Returns infos about the user
	 * 
	 * @return array 
	 */
	public function whoAmI()
	{
		$res = $this->request('/whoami');
		return $res;
	}
	/**
	 * Returns an array of docs
	 * 
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listDocs
	 * @return array 
	 */
	public function listDocs(array $params = [])
	{
		$res = $this->request('/docs?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns information of a doc
	 * 
	 * @param string $doc Id of a doc
	 * @return array 
	 */
	public function getDoc($doc)
	{
		$res = $this->request('/docs/'.$doc);
		return $res;
	}
	/**
	 * Creates a new doc
	 * 
	 * @param string $title Name of the doc
	 * @param string $source Id of a doc to use as source (creates a copy)
	 * @return array 
	 */
	public function createDoc($title = '', $source = '', $folderId = '', $timezone = '')
	{
		$params['title'] = $title;
		$params['sourceDoc'] = $source;
		$params['folderId'] = $folderId;
		$params['timezone'] = $timezone;
		$res = $this->request('/docs', ['json' => $params], 'POST');
		return $res;
	}
	/**
	 * Returns pages in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listPages
	 * @return array
	 */
	public function listPages($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/pages?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a page in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $page Id or name of a page
	 * @return array
	 */
	public function getPage($doc, $page)
	{
		$res = $this->request('/docs/'.$doc.'/pages/'.$this->prepareStrings($page));
		return $res;
	}
	/**
	 * Returns tables or views in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listTables
	 * @return array
	 */
	public function listTables($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/tables?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a table or a view in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @return array
	 */
	public function getTable($doc, $table) 
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table));
		return $res;
	}
	/**
	 * Returns columns in a table or view
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listColumns
	 * @return array
	 */
	public function listColumns($doc, $table, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/columns?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a column in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param string $column Id or name of a column
	 * @return array
	 */
	public function getColumn($doc, $table, $column)
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/columns/'.$this->prepareStrings($column));
		return $res;
	}
	/**
	 * Returns rows in a table or view
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listRows , useColumnNames is set true by default
	 * @return array
	 */
	public function listRows($doc, $table, array $params = [])
	{
		$params['useColumnNames'] = $params['useColumnNames'] ?? true; 
		if(isset($params['query'])) {
			$params['query'] = $this->array_key_first($params['query']).':"'.reset($params['query']).'"';
		};
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows?'.http_build_query($params));
		return $res;
	}
	/**

	/**
	 * Inserts or updates a row in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param array $rowData Associative array with your row data. Can be one row as array or an array of mulitple rows as an array (arrayception). Keys has to be column ids or names.
	 * @param array $keyColumns Array with ids or names of columns. Coda will update rows instead of inserting, if keyColumns are matching
	 * @param  bool $disableParsing Disables automatic column format parsing. Default false.
	 * @return bool
	 */
	public function insertRows($doc, $table, array $rowData, array $keyColumns = [], $disableParsing = false)
	{
		if($this->countDimension($rowData) == 1)
			$rowData = [$rowData]; 
		$i = 0;
		foreach($rowData as $row) {
			foreach($row as $column => $value) {
				$params['rows'][$i]['cells'][] = ['column' => $column, 'value' => $value];
			}
			$i++;
		}
		$params['keyColumns'] = $keyColumns;
		$query['disableParsing'] = $disableParsing;
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows', ['query' => $query, 'json' => $params], 'POST', true);
		if($res['statusCode'] === 202) {
			return true;
		} else {
			return $res;
		}
	}
	/**
	 * Returns a row in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param string $row Id or name of a column
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/getRow , useColumnNames is set true by default
	 * @return array
	 */
	public function getRow($doc, $table, $row, array $params = [])
	{
		$params['useColumnNames'] = $params['useColumnNames'] ?? true; 
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row).'?'.http_build_query($params));
		return $res;
	}
	/**
	 * Updates a row in a table or view
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @param string $row Id or name of a row
	 * @param array $rowData Associative array with your row data
	 * @param  bool $disableParsing Disables automatic column format parsing. Default false.
	 * @return string Id of the updated row
	 */
	public function updateRow($doc, $table, $row, array $rowData, $disableParsing = false)
	{
		foreach($rowData as $column => $value) {
			$params['row']['cells'][] = ['column' => $column, 'value' => $value];
		}
		$query['disableParsing'] = $disableParsing;
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row), ['query' => $query, 'json' => $params], 'PUT');
		return $res;
	}
	/**
	 * Deletes a row in a table or view
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @param string $row Id or name of a row
	 * @return string Id of the deleted row
	 */
	public function deleteRow($doc, $table, $row)
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row), [], 'DELETE');
		return $res;
	}

	/**
	 * Pushes a button in a table or view
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table or view
	 * @param string $row Id or name of a row
	 * @param string $column Id or name of a column
	 * @return string Id of the deleted row
	 */
	public function pushButton($doc, $table, $row, $column)
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row).'/buttons/'.$this->prepareStrings($column), [], 'POST');
		return $res;
	}
	/**
	 * Returns formulas in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listFormulas
	 * @return array
	 */
	public function listFormulas($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/formulas?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a formula in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $formula Id or name of a formula
	 * @return array
	 */
	public function getFormula($doc, $formula)
	{
		$res = $this->request('/docs/'.$doc.'/formulas/'.$this->prepareStrings($formula));
		return $res;
	}
	/**
	 * Returns controls in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#operation/listControls
	 * @return array
	 */
	public function listControls($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/controls?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a control in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $control Id or name of a control
	 * @return array
	 */
	public function getControl($doc, $control)
	{
		$res = $this->request('/docs/'.$doc.'/controls/'.$this->prepareStrings($control));
		return $res;
	}
	/**
	 * Returns mutation status of asynchronous mutation
	 * 
	 * @param string $requestId Id of a request
	 * @return array
	 */
	public function getMutationStatus($requestId)
	{
		$res = $this->request('/mutationStatus/'.$requestId, [], 'GET', false, false);
		return $res;
	}
	/**
	 * Resolves a link
	 * 
	 * @param string $url Url of a doc
	 * @return array
	 */
	public function resolveLink($url)
	{
		$res = $this->request('/resolveBrowserLink?url='.$this->prepareStrings($url), [], 'GET', false, false);
		return $res;
	}
	/**
	 * Returns ACL medadata
	 * 
	 * @param string $doc Id of a doc
	 * @return array
	 */
	public function getACLMeta($doc)
	{
		$res = $this->request('/docs/'.$doc.'/acl/metadata');
		return $res;
	}
	/**
	 * Returns a list of permissions
	 * 
	 * @param string $doc Id of a doc
	 * @return array
	 */
	public function listPermissions($doc)
	{
		$res = $this->request('/docs/'.$doc.'/acl/permissions', [], 'GET', false, false);
		return $res;
	}
	/**
	 * Adds permissions to the doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $access type of access (readonly, write, comment, none)
	 * @param string|array $principal metadata about a principal
	 * @param bool $notify if true, sends notification email
	 * @return array
	 */
	public function addPermission($doc, $access, $principal, $notify = false)
	{
		$params['access'] = $access;
		$params['principal'] = $principal;
		$params['suppressEmail'] = !$notify;
		$res = $this->request('/docs/'.$doc.'/acl/permissions', ['json' => $params], 'POST');
		return $res;
	}
	/**
	 * Deletes permissions to the doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $permissionId the id of the permission entry
	 * @return array
	 */
	public function deletePermission($doc, $permissionId)
	{
		$res = $this->request('/docs/'.$doc.'/acl/permissions/'.$permissionId, [], 'DELETE');
		return $res;
	}
	/**
	 * Adds a user to the doc (shortcut for permissions methods)
	 * 
	 * @param string $doc Id of a doc
	 * @param string $email email address of a user
	 * @param string $access type of access (readonly, write, comment, none)
	 * @param bool $notify if true, sends notification email
	 * @return mixed
	 */
	public function addUser($doc, $email, $access = "write", $notify = false)
	{
		$principal = [
			'type' => 'email',
			'email' => $email
		];
		return $this->addPermission($doc, $access, $principal, $notify);
	}
	/**
	 * Removes a user from the doc (shortcut for permissions methods)
	 * 
	 * @param string $doc Id of a doc
	 * @param string $email email address of a user
	 * @return mixed
	 */
	public function deleteUser($doc, $email)
	{
		$permissions = $this->listPermissions($doc);
		foreach($permissions['items'] as $permission) {
			if($permission['principal']['email'] == $email) {
				$id = $permission['id'];
			}
		}
		if(isset($id)) {
			return $this->deletePermission($doc, $id);
		} else {
			return false;
		}
	}
	/**
	 * Runs an automation of type "webhook invoked" in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $automation Id of the automation rule
	 * @return mixed
	 */
	public function runAutomation($doc, $ruleId)
	{
		$res = $this->request('/docs/'.$doc.'/hooks/automation/'.$ruleId, [], 'POST');
		return $res;
	}

	/**
	 * Returns analytics data for available docs per day.
	 * 
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listDocAnalytics
	 * @return array
	 */
	public function listDocAnalytics(array $params = [])
	{
		$res = $this->request('/analytics/docs?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns analytics data for a given doc within the day. This method will return a 401 if the given doc is not in a Team or Enterprise workspace.
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listPageAnalytics
	 * @return array
	 */
	public function listPageAnalytics($doc, array $params = [])
	{
		$res = $this->request('/analytics/docs/'.$doc.'/pages?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns analytics data for Packs the user can edit..
	 * 
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listPackAnalytics
	 * @return array
	 */
	public function listPackAnalytics(array $params = [])
	{
		$res = $this->request('/analytics/packs?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns analytics data for Pack formulas.
	 * 
	 * @param string $pack Id of a pack
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listPackFormulaAnalytics
	 * @return array
	 */
	public function listPackFormulaAnalytics($pack, array $params = [])
	{
		$res = $this->request('/analytics/packs/'.$pack.'/formulas?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns summarized analytics data for available docs.
	 * 
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listDocAnalyticsSummary
	 * @return array
	 */
	public function getDocAnalyticsSummary(array $params = [])
	{
		$res = $this->request('/analytics/docs/summary?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns summarized analytics data for Packs the user can edit.
	 * 
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1#tag/Analytics/operation/listPackAnalyticsSummary
	 * @return array
	 */
	public function getPackAnalyticsSummary(array $params = [])
	{
		$res = $this->request('/analytics/packs/summary?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns days based on Pacific Standard Time when analytics were last updated.
	 * 
	 * @return array
	 */
	public function getAnalyticsUpdatedDay()
	{
		$res = $this->request('/analytics/updated');
		return $res;
	}

	/**
	 * Cleares the cache folder
	 * 
	 * @param 
	 */
	public function clearCache()
	{
		array_map( 'unlink', array_filter((array) glob(self::CACHE_DIR.'*') ) );
	}
	/**
	 * Counts dimensions of an array
	 * 
	 * @param array $array
	 * @return int
	 */
	protected function countDimension($array)
	{
		if (is_array(reset($array))) { $return = $this->countDimension(reset($array)) + 1; } else { $return = 1; }
		return $return;
	}
	/**
	 * Prepares strings to be used in url
	 * 
	 * @param string $string
	 * @return string
	 */
	protected function prepareStrings($string) {
		// urleconde converts space to + but Coda can only read space as space or as %20. A little workaround encodes the string and converts space to %20 instead of +.
		$parts = array_map('urlencode', explode(' ', $string));
		return implode('%20', $parts);
	}
	/**
	 * Gets the first key of an array. Standard function in PHP >= 7.3.0.
	 * 
	 * @param array $array
	 * @return mixed
	 */
	protected function array_key_first(array $array)
	{
	    return $array ? array_keys($array)[0] : null;
	}
}