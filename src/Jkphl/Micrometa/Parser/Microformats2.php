<?php

/**
 * micrometa – Micro information meta parser
 *
 * @category	Jkphl
 * @package		Jkphl_Micrometa
 * @author		Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright	Copyright © 2015 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license		http://opensource.org/licenses/MIT	The MIT License (MIT)
 */

namespace Jkphl\Micrometa\Parser;

/***********************************************************************************
 *  The MIT License (MIT)
 *  
 *  Copyright © 2015 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy of
 *  this software and associated documentation files (the "Software"), to deal in
 *  the Software without restriction, including without limitation the rights to
 *  use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 *  the Software, and to permit persons to whom the Software is furnished to do so,
 *  subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 *  FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *  COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 *  IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 ***********************************************************************************/

require_once __DIR__.DIRECTORY_SEPARATOR.'Microformats2'.DIRECTORY_SEPARATOR.'Exception.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'Microformats2'.DIRECTORY_SEPARATOR.'Item.php';

// Include the Composer autoloader
if (@is_file(dirname(dirname(dirname(dirname(__DIR__)))).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php')) {
	require_once dirname(dirname(dirname(dirname(__DIR__)))).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
}

// Exit on failure
if (!@class_exists('\Mf2\Parser')) {
	die ((PHP_SAPI == 'cli') ?
		"\nPlease follow the instructions at https://github.com/jkphl/micrometa#dependencies to install the library containing the PHP class \"\Mf2\Parser\".\n\n" :
		'<p style="font-weight:bold;color:red">Please follow the <a href="https://github.com/jkphl/micrometa#dependencies" target="_blank">instructions</a> to install the library containing the PHP class "\Mf2\Parser"</p>'
	);
}

/**
 * Extended Microformats2 parser
 * 
 * @category	Jkphl
 * @package		Jkphl_Micrometa
 * @author		Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @copyright	Copyright © 2015 Joschi Kuphal <joschi@kuphal.net> / @jkphl
 * @license		http://opensource.org/licenses/MIT	The MIT License (MIT)
 * @link		https://github.com/indieweb/php-mf2
 */
class Microformats2 extends \Mf2\Parser {
	/**
	 * Original resource URL
	 *
	 * @var \Jkphl\Utility\Url
	 */
	protected $_url = null;
	
	/************************************************************************************************
	 * PUBLIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Constructor
	 *
	 * @param \DOMDocument|string $input			The data to parse. A string of HTML or a DOMDocument
	 * @param \Jkphl\Utility\Url|\string $url		Optional: The URL of the parsed document, for relative URL resolution
	 */
	public function __construct($input, $url = null) {
		$this->_url							= ($url instanceof \Jkphl\Utility\Url) ? $url : new \Jkphl\Utility\Url($url);
		parent::__construct($input, strval($url));
	}
	
	/**
	 * Kicks off the parsing routine
	 * 
	 * If `$convertClassic` is set, any angle brackets in the results from non e-* properties
	 * will be HTML-encoded, bringing all output to the same level of encoding.
	 * 
	 * If a DOMElement is set as the $context, only descendants of that element will
	 * be parsed for microformats.
	 * 
	 * @param bool $convertClassic					Whether or not to html-encode non e-* properties. Defaults to false
	 * @param DOMElement $context					Optionall: An element from which to parse microformats
	 * @return array								An array containing all the µfs found in the current document
	 */
	public function parse($convertClassic = true, \DOMElement $context = null) {
		$results				= parent::parse($convertClassic, $context);
		$results['items']		= $this->_refineResults($results['items']);
		return $results;
	}
	
	/************************************************************************************************
	 * PRIVATE METHODS
	 ***********************************************************************************************/
	
	/**
	 * Refine micro information items
	 * 
	 * @param \array $results						Micro information items
	 * @return \array								Refined micro information items
	 */
	protected function _refineResults(array $results) {
		$refined				= array();
		
		// Run through all original parsing results
		foreach ($results as $data) {
			$refined[]			= new \Jkphl\Micrometa\Parser\Microformats2\Item($data, $this->_url);
		}
		
		return $refined;
	}
	
	/************************************************************************************************
	 * STATIC METHODS
	 ***********************************************************************************************/
	
	/**
	 * Check if a string is a valid microformats2 vocable (regular or camelCased)
	 * 
	 * @param \string $str							String
	 * @return \boolean								Whether it's a valid microformats2 vocable
	 */
	public static function isValidVocable($str) {
		return preg_match("%^[a-z]+([A-Z][a-z]*)*$%", $str) || preg_match("%^[a-z]+(\-[a-z]+)*$%", $str); 
	}
	
	/**
	 * Decamelize a lower- or UpperCameCase microformats2 vocable (has no effect on regular vocables)
	 * 
	 * @param \string $vocable			Vocable
	 * @param \string $separator		Separation char / vocable
	 * @return \string					Decamelized vocable
	 * @throws \Jkphl\Micrometa\Parser\Microformats2\Exception		If it's not a valid microformats2 vocable
	 */
	public static function decamelize($vocable, $separator = '-') {
		if (!self::isValidVocable($vocable)) {
			throw new \Jkphl\Micrometa\Parser\Microformats2\Exception(sprintf(\Jkphl\Micrometa\Parser\Microformats2\Exception::INVALID_MICROFORMAT_VOCABLE_STR, $vocable), \Jkphl\Micrometa\Parser\Microformats2\Exception::INVALID_MICROFORMAT_VOCABLE);
		}
		return strtolower(preg_replace("%[A-Z]%", "$separator$0", $vocable));
	}
}