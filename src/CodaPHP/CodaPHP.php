<?php
namespace CodaPHP;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * CodaPHP Library
 * 
 * CodaPHP is a library that makes it easy to use data from Coda (https://www.coda.io)
 * docs your in web projects by using the Coda API (Beta) (https://coda.io/developers/apis/v1beta1).
 * Coda itself as well as the API is still in beta. Use on your own risk.
 * 
 * This file is licensed under the MIT license.
 */
class CodaPHP
{
	/**
	 * The API Token and the guzzle client
	 * @var string $apiToken
	 * @var obj $client
	 */
	protected $apiToken, $client;

	/**
	 * Base URL of the Coda Api
	 */
	const API_BASE = 'https://coda.io/apis/v1beta1';

	/**
	 * Creates new guzzle client with authentication
	 */
	public function __construct($apiToken)
	{
		$this->apiToken = $apiToken;
		$this->client = new Client([
			'headers' => [
				'Authorization' => 'Bearer '.$this->apiToken
			]
		]);
	}
	/**
	 * Performs a request using guzzle
	 * 
	 * @param string $url
	 * @param array $params Guzzle request params
	 * @param string $method HTTP request method
	 * @param bool $addstatus When true, the return will include the HTTP status code
	 * @return array
	 */
	protected function request($url, array $params = [], $method = 'GET', $addStatus = false)
	{
		try {
			$response = $this->client->request($method, self::API_BASE . $url, $params);
		} catch (BadResponseException $e) {
			$errorContent = $e->getResponse()->getBody()->getContents();
			return(json_decode($errorContent, true));
		}
		$httpCode = $response->getStatusCode();
		$responseString = $response->getBody()->getContents();
		$dataArray = json_decode($responseString, true);

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
		$re = '/coda.io\/d\/.*?_d(.{10})\//m';
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
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listDocs
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
	public function createDoc($title = '', $source = '')
	{
		$params['title'] = $title;
		$params['sourceDoc'] = $source;
		$res = $this->request('/docs', ['json' => $params], 'POST');
		return $res;
	}
	/**
	 * Returns sections in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listSections
	 * @return array
	 */
	public function listSections($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/sections?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a section in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $section Id or name of a section
	 * @return array
	 */
	public function getSection($doc, $section)
	{
		$res = $this->request('/docs/'.$doc.'/sections/'.$this->prepareStrings($section));
		return $res;
	}
	/**
	 * Returns folders in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listFolders
	 * @return array
	 */
	public function listFolders($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/folders?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a folder in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $folder Id or name of a folder
	 * @return array
	 */
	public function getFolder($doc, $folder)
	{
		$res = $this->request('/docs/'.$doc.'/folders/'.$this->prepareStrings($folder));
		return $res;
	}
	/**
	 * Returns tables in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listTables
	 * @return array
	 */
	public function listTables($doc, array $params = [])
	{
		$res = $this->request('/docs/'.$doc.'/tables?'.http_build_query($params));
		return $res;
	}
	/**
	 * Returns a table in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @return array
	 */
	public function getTable($doc, $table) 
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table));
		return $res;
	}
	/**
	 * Returns columns in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listColumns
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
	 * Returns rows in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listRows , useColumnNames is set true by default
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
	 * Inserts or updates a row in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param array $rowData Associative array with your row data. Can be one row as array or an array of mulitple rows as an array (arrayception). Keys has to be column ids or names.
	 * @param array $keyColumns Array with ids or names of columns. Coda will update rows instead of inserting, if keyColumns are matching
	 * @return bool
	 */
	public function insertRows($doc, $table, array $rowData, array $keyColumns = [])
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
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows', ['json' => $params], 'POST', true);
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
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/getRow , useColumnNames is set true by default
	 * @return array
	 */
	public function getRow($doc, $table, $row, array $params = [])
	{
		$params['useColumnNames'] = $params['useColumnNames'] ?? true; 
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row).'?'.http_build_query($params));
		return $res;
	}
	/**
	 * Updates a row in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param string $row Id or name of a row
	 * @param array $rowData Associative array with your row data
	 * @return string Id of the updated row
	 */
	public function updateRow($doc, $table, $row, array $rowData)
	{
		foreach($rowData as $column => $value) {
			$params['row']['cells'][] = ['column' => $column, 'value' => $value];
		}
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row), ['json' => $params], 'PUT');
		return $res;
	}
	/**
	 * Deltes a row in a table
	 * 
	 * @param string $doc Id of a doc
	 * @param string $table Id or name of a table
	 * @param string $row Id or name of a row
	 * @return string Id of the deleted row
	 */
	public function deleteRow($doc, $table, $row)
	{
		$res = $this->request('/docs/'.$doc.'/tables/'.$this->prepareStrings($table).'/rows/'.$this->prepareStrings($row), [], 'DELETE');
		return $res;
	}
	/**
	 * Returns formulas in a doc
	 * 
	 * @param string $doc Id of a doc
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listFormulas
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
	 * @param array $params Optional query parameters listed here https://coda.io/developers/apis/v1beta1#operation/listControls
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
	 * Resolves a link
	 * 
	 * @param string $url Url of a doc
	 * @return array
	 */
	public function resolveLink($url)
	{
		$res = $this->request('/resolveBrowserLink?url='.$this->prepareStrings($url));
		return $res;
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