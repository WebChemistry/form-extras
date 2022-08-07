<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Renderer;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\FormsLatte\FormMacros;
use Nette\Bridges\FormsLatte\FormsExtension;
use WebChemistry\FormExtras\Renderer\Template\LatteFormRendererTemplate;

final class LatteFormRendererFactory
{

	public function __construct(
		private LatteFactory $latteFactory,
	)
	{
	}

	public function create(string $file): LatteFormRenderer
	{
		$engine = $this->latteFactory->create();

		if (version_compare(Engine::VERSION, '3', '<')) {
			$engine->onCompile[] = function (Engine $engine): void {
				FormMacros::install($engine->getCompiler());
			};
		} else {
			$engine->addExtension(new FormsExtension());
		}

		$template = new LatteFormRendererTemplate($engine);
		$template->setFile($file);

		return new LatteFormRenderer($template);
	}

}
