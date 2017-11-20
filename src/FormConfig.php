<?php
namespace FormConfig;


use Nette\Application\UI\Form;
use Nette\ComponentModel\IComponent;
use Nette\Neon\Neon;
use Nette\Utils\Json;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

use Nette\FileNotFoundException;
use Nette\InvalidArgumentException;
use Nette\NotSupportedException;

use ArrayObject;
use Exception;
use function file_exists;
use function get_class;
use function is_array;


class FormConfig {

	public static function getConfig($config)
	{
		if (is_string($config)) {

			list(, $ext) = Strings::match($config, '~\.(neon|yml|yaml|json|php)$~');
			if ($ext) {
				if (!file_exists($config)) throw new FileNotFoundException("form config file $config not found");
				switch ($ext) {
					case 'php':
						$config = include($config);
						break;
					case 'neon':
					case 'yaml':
					case 'yml':
						$content = file_get_contents($config);
						$config = Neon::decode($content);
						break;
					case 'json':
						$content = file_get_contents($config);
						$config = Json::decode($content);
						break;
				}
			}

		}
		if (!is_array($config)) throw new NotSupportedException('invalid form config file format or extension');
		return $config;
	}

	public static function create(IComponent $component, $config) {
		return new static($component, $config);
	}

	protected $path = [];
	protected $data = [];
	protected $id = 0;


	public function __construct(IComponent $component, $config)
	{
		$config = static::getConfig($config);
		#$component->addText('name', 'Name:');
		#$component->addPassword('password', 'Password:');
		#$component->addSubmit('login', 'Sign up');
		#$component->onSuccess[] = [$component, 'onSuccess'];

		try {
			$this->id = 0;
			$this->path = [];
			$this->createElement($component, 'form', $config['form']);
		}
		catch (Exception $e) {
			$class = get_class($e);
			$msg = $e->getMessage().' in formlayout in '.implode('/', $this->path);
			throw new $class($msg);
		}
	}

	protected function createElement($container, string $type, $config) {
		$this->id++;
		$config = $this->sanitizeConfig($config);
		$this->path[] = $type . ($config->name ? ':'.$config->name : '');

		list(, $prefix, $type) = Strings::match($type, '~^(?:(static|)-?)(\w+)$~');
		$type = Strings::lower($type);
		
		switch ($prefix) {
			case 'static': $config->disabled = true; break;
		}

		switch ($type) {
			case 'form':
				$config->data = null;
				$this->applyConfig($container, $config);
				break;
			case 'group':
				$this->applyElement($container,'addGroup', $config);
				break;
			case 'hidden':
				$this->applyElement($container,'addHidden', $config);
				break;
			case 'text':
				$this->applyElement($container,'addTextArea', $config);
				break;
			case 'static':
				$config->disabled = true;
				$this->applyElement($container,'addText', $config);
				break;
			case 'string':
			case 'input':
				$this->applyElement($container,'addText', $config);
				break;
			case 'number':
				$config->format = '\d*(\.\d+)?';
				$config->align = 'right';
				$this->applyElement($container,'addText', $config);
				break;
			case 'password':
				$this->applyElement($container,'addPassword', $config);
				break;
			case 'select':
				$this->applyElement($container,'addSelect', $config);
				break;
			case 'check':
				$this->applyElement($container,'addCheckbox', $config);
				break;
			case 'submit':
			case 'button':
				$config->data = null;
				$config->value = $config->name;
				$config->name = '__action'.$this->id;
				$container = $this->applyElement($container, 'addSubmit', $config);
				#$container->setAttribute('type', 'button');


				break;
			default:
				throw new InvalidArgumentException("unknown type $type");
		}

		array_pop($this->path);
	}

	protected function sanitizeConfig($aCfg) {
		if (!is_array($aCfg)) $aCfg = [$aCfg];
		if (isset($aCfg[0])) { $aCfg['name'] = $aCfg[0]; unset($aCfg[0]); };
		if (isset($aCfg[1])) { $aCfg['label'] = $aCfg[1]; unset($aCfg[1]); };
		if (isset($aCfg[2])) { $aCfg['data'] = $aCfg[2]; unset($aCfg[2]); };
		$aCfg = array_merge([
			'name'  => '__element'.$this->id,
			'label' => '',
		], $aCfg);
		$aCfg = array_merge([
			'data'  => $aCfg['name'],
		], $aCfg);

		//return new ArrayHash($aCfg);
		return new ArrayObject($aCfg, \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS);
	}

	protected function applyElement($container, string $method, \Traversable $config) {
		if ($config->data) $this->data[] = $config->data;

		$container = $container->$method($config->name, $config->label);
		$this->applyConfig($container, $config);

		if ($config->data) array_pop($this->data);
		return $container;
	}

	protected function applyConfig($container, \Traversable $config, array $aAllowed = null) {

		foreach ($config as $param => $value) {

			switch ($param) {
				case 'name':
				case 'label':
					break;
				case 'data':
					$this->setData($container, $value);
					break;
				case 'value':
					$this->setValue($container, $value);
					break;
				case 'disabled':
				case 'disable':
					$this->setDisable($container, $value);
					break;
				case 'enabled':
				case 'enable':
					$this->setDisable($container, $value, true);
					break;
				case 'shown':
				case 'show':
					$this->setShow($container, $value);
					break;
				case 'hidden':
				case 'hide':
					$this->setShow($container, $value, true);
					break;
				case 'rows':
					$this->setRows($container, $value);
					break;
				case 'hint':
					$this->setHint($container, $value);
					break;
				case 'format':
					$this->setFormat($container, $value);
					break;
				case 'align':
					$this->setAlign($container, $value);
					break;
				case 'options':
					$this->setOptions($container, $value);
					break;
				case 'confirm':
					$this->setConfirm($container, $value);
					break;
				default:
					if (list($event) = Strings::match($param, '~^on([A-Z]\w+)$~')) $this->applyEvent($container, $event, $value);
					else throw new InvalidArgumentException("unknown param $param");
			}
		}
	}

	protected function setRows($container, $aRows) {
		foreach ($aRows as $key => $aRow) {
			if (!is_numeric($key)) $aRow = [$key => $aRow];

			foreach ($aRow as $type => $aDef) {
				$this->createElement($container, $type, $aDef);
			}
		}
	}

	protected function setData($container, $name) {
		if (empty($name)) return;
		#bdump($container, $name);
		$name = implode('.', $this->data);
		#$this->oDataResolver->add($name, $container);
	}

	protected function setValue($container, $value) {
		if (!method_exists($container, 'setDefaultValue')) return $this->raiseError("value param not allowed");
		$container->setDefaultValue($value);
	}

	protected function setOptions($container, $value) {
		if (!method_exists($container, 'setItems')) return $this->raiseError("options param not allowed");
		$container->setItems($value, Arrays::isList($value));
	}

	protected function setDisable($container, $value, bool $invert = false) {
		if (is_bool($value)) $container->setDisabled($value ^ $invert);
		elseif (is_string($value)) {
			// TODO: add rules
		}
		else {
			throw new InvalidArgumentException("unknown disable value type $value");
		}
	}

	protected function setShow($container, $value, bool $invert = false) {
		if (is_bool($value)) $container->setHtmlAttribute('style', 'display: '.($value ^ $invert) ? 'inherit' : 'none');
		elseif (is_string($value)) {
			// TODO: add rules
		}
		else {
			throw new InvalidArgumentException("unknown disable value type $value");
		}
	}

	protected function setHint($container, $value) {
		if (is_string($value)) $container->setHtmlAttribute('title', $value);
		else {
			throw new InvalidArgumentException("unknown hint value type $value");
		}
	}

	protected function setFormat($container, $value) {
		if (!is_string($value)) throw new InvalidArgumentException("unknown confirm value type $value");
		$container->addRule(Form::PATTERN, 'not a number', $value);
		$container->setRequired(false);
	}

	protected function setAlign($container, $value) {
		if (is_string($value)) $container->setHtmlAttribute('align', $value);
		else {
			throw new InvalidArgumentException("unknown align value type $value");
		}
	}

	protected function setConfirm($container, $value) {
		if (is_string($value)) $container->setHtmlAttribute('onclick', "return window.confirm('$value');");
		else {
			throw new InvalidArgumentException("unknown confirm value type $value");
		}
	}

/*

	public function applyData($oData) {
		$this->oDataResolver->resolve($oData, function($container, $value) { $this->setValue($container, $value); });
	}


	protected function applyEvent($container, $event, $value) {
		if (!method_exists($container, 'setHtmlAttribute')) return $this->raiseError("event param not allowed");
		$container->setHtmlAttribute('form-'.$event, $value);
	}

*/
}