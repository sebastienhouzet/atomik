<?php
/**
 * Atomik Framework
 * Copyright (c) 2008-2009 Maxime Bouroumeau-Fuseau
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package Atomik
 * @subpackage Form
 * @author Maxime Bouroumeau-Fuseau
 * @copyright 2008-2009 (c) Maxime Bouroumeau-Fuseau
 * @license http://www.opensource.org/licenses/mit-license.php
 * @link http://www.atomikframework.com
 */

/** Atomik_Form */
require_once 'Atomik/Form.php';

/**
 * @package Atomik
 * @subpackage Form
 */
class Atomik_Form_Class extends Atomik_Form
{
	/**
	 * @var string
	 */
	protected $_formTagPrefix = '';
	
	/**
	 * Creates an Atomik_Form instance from a class
	 * 
	 * @param	string		$className
	 * @param	string		$tagsPrefix
	 * @return 	Atomik_Form
	 */
	public static function create($className, $tagsPrefix = '')
	{
		if (is_object($className)) {
			$className = get_class($className);
		}
		$class = new ReflectionClass($className);
		return self::createInstance($class, null, $tagsPrefix);
	}
	
	/**
	 * Creates a form instance from a class doc comments
	 * 
	 * @param	ReflectionClass	$class
	 * @param 	Atomik_Form		$instance
	 * @return 	Atomik_Form
	 */
	public static function createInstance(ReflectionClass $class, $instance = null, $tagsPrefix = '')
	{
		if ($instance === null) {
			$instance = new Atomik_Form();
		}
		
		$attributes = self::getTagsFromDocBlock($class->getDocComment(), $tagsPrefix);
		
		if (isset($attributes['template'])) {
			$instance->setFormTemplate($attributes['template']);
			unset($attributes['template']);
		}
		
		if (isset($attributes['field-template'])) {
			$instance->setFieldTemplate($attributes['field-template']);
			unset($attributes['field-template']);
		}
		
		$instance->setFields(self::getFieldsFromClass($class, $tagsPrefix));
		$instance->setAttributes($attributes);
		
		return $instance;
	}
	
	/**
	 * Returns an array of Atomik_Form_Fields created using properties of a class
	 * 
	 * @param	ReflectionClass	$class
	 * @param	string			$tagsPrefix
	 * @return 	array
	 */
	public static function getFieldsFromClass(ReflectionClass $class, $tagsPrefix = '')
	{
		$fields = array();
		
		foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
			$options = self::getTagsFromDocBlock($prop->getDocComment(), $tagsPrefix);
			if (isset($options['ignore'])) {
				continue;
			}
			
			$type = 'Input';
			if (isset($options['field'])) {
				$type = $options['field'];
				unset($options['field']);
			}
			
			$field = Atomik_Form_Field_Factory::factory($type, $prop->getName());
			$label = $prop->getName();
			
			if (isset($options['label'])) {
				$label = $options['label'];
				unset($options['label']);
			}
			
			$field->setOptions($options);
			$fields[$label] = $field;
		}
		
		return $fields;
	}
	
	/**
	 * Retreives tags (i.e. starting with @) from a doc block
	 *
	 * @param 	string 	$doc
	 * @param	string	$prefix
	 * @return 	array
	 */
	public static function getTagsFromDocBlock($doc, $prefix = '')
	{
		$tags = array();
		$regexp = '/@' . $prefix . '(.+)$/mU';
		preg_match_all($regexp, $doc, $matches);
		
		for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
			if (($separator = strpos($matches[1][$i], ' ')) !== false) {
				$key = trim(substr($matches[1][$i], 0, $separator));
				$value = trim(substr($matches[1][$i], $separator + 1));
			} else {
				$key = trim($matches[1][$i]);
				$value = true;
			}
			
			if (isset($tags[$key])) {
				if (!is_array($tags[$key])) {
					$tags[$key] = array($tags[$key]);
				}
				$tags[$key][] = $value;
			} else {
				$tags[$key] = $value;
			}
		}
		
		return $tags;
	}
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$class = new ReflectionClass(get_class($this));
		self::createInstance($class, $this, $this->_formTagPrefix);
		
		$this->setData(array_merge($_POST, $_FILES));
	}
	
	/**
	 * Sets the data
	 * 
	 * @param	array	$data
	 */
	public function setData($data)
	{
		parent::setData($data);
		
		if (!empty($data)) {
			foreach ($this->getData() as $key => $value) {
				$this->{$key} = $value;
			}
		}
	}
}