<?php
namespace App;

use Nette;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter as BasePresenter;

class TestPresenter extends BasePresenter {

	public function createComponentTestForm(): Form
	{
		$form = new TestForm([$this, 'onOk']);
		return $form;
	}

	public function onOk($name, $values) {
		$this->flashMessage('form ok: '.$name);
	}

}