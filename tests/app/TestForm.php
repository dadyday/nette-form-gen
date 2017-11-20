<?php
namespace App;

use function is_array;
use Nette;
use Nette\Application\UI\Form as BaseForm;
use FormConfig\FormConfig;
use function call_user_func;
use Nette\Utils\Arrays;
use Nette\Utils\Callback;
use Nette\Utils\Validators;


class TestForm extends BaseForm {

	var $handlers;

	public function __construct($handlers) {
		if (Callback::check($handlers)) $handlers = ['_default' => $handlers];
		if (!is_array($handlers) || Arrays::isList($handlers)) throw new Nette\InvalidArgumentException('handlers must be a hash or callable');
		$this->handlers = array_merge(['_default' => null], $handlers);

		FormConfig::create($this, __DIR__.'/testform.neon');
		$this->onSuccess[] = [$this, 'onSuccess'];
	}

	public function onSuccess($form, $values) {
		$data = $form->getHttpData();
		bdump($data);
		$event = Arrays::pick($data, 'success', '_default');
		$handler = Arrays::pick($this->handlers, $event, $this->handlers['_default']);
		Callback::invoke($handler, $event, $values);
	}



}