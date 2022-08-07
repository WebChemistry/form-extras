<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Renderer;

use Nette\Forms\Form;
use Nette\Forms\FormRenderer;
use WebChemistry\FormExtras\Renderer\Template\LatteFormRendererTemplate;

final class LatteFormRenderer implements FormRenderer
{

	public function __construct(
		private LatteFormRendererTemplate $template,
	)
	{
	}

	public function withTemplate(LatteFormRendererTemplate $template): self
	{
		$clone = clone $this;
		$clone->template = $template;

		return $clone;
	}

	public function render(Form $form): string
	{
		$this->template->form = $form;

		return $this->template->renderToString();
	}

}
