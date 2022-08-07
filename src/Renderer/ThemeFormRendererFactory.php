<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Renderer;

use Nette\Bridges\ApplicationLatte\LatteFactory;
use WebChemistry\FormExtras\Theme\FormTheme;

final class ThemeFormRendererFactory
{

	private LatteFormRendererFactory $latteFormRendererFactory;

	public function __construct(LatteFactory $latteFactory)
	{
		$this->latteFormRendererFactory = new LatteFormRendererFactory($latteFactory);
	}

	public function create(FormTheme $theme): LatteFormRenderer
	{
		return $this->latteFormRendererFactory->create($theme->getTemplateFile());
	}

}
