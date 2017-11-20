<?php
// source: D:\web\projects\hheyne\nette-form-gen\tests\app/templates/Test/default.latte

use Latte\Runtime as LR;

class Templatedd63f205ae extends Latte\Runtime\Template
{
	public $blocks = [
		'content' => 'blockContent',
	];

	public $blockTypes = [
		'content' => 'html',
	];


	function main()
	{
		extract($this->params);
		if ($this->getParentName()) return get_defined_vars();
		$this->renderBlock('content', get_defined_vars());
		return get_defined_vars();
	}


	function prepare()
	{
		extract($this->params);
		Nette\Bridges\ApplicationLatte\UIRuntime::initialize($this, $this->parentName, $this->blocks);
		
	}


	function blockContent($_args)
	{
		extract($_args);
?>

Here we are!
<a href="<?php echo LR\Filters::escapeHtmlAttr($this->global->uiControl->link("Test:form")) ?>"><?php echo LR\Filters::escapeHtmlText($this->global->uiControl->link("Test:Form")) ?></a>
<?php
	}

}
