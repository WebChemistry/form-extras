<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Theme;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Form;
use Nette\Forms\FormRenderer;
use Nette\HtmlStringable;
use Nette\Utils\Html;
use WebChemistry\FormExtras\Extension\FormWithOptions;
use WebChemistry\FormExtras\Renderer\ThemeFormRendererFactory;
use WebChemistry\FormExtras\Theme\Html\HtmlElement;

class DefaultFormTheme implements FormTheme
{

	public bool $asteriskInCaption = false;

	public array $form = [
		'attributes' => [
			'class' => 'form-horizontal',
		],
	];

	public array $formErrors = [
		'element' => 'ul',
		'attributes' => [
			'class' => 'form-errors',
		],
	];

	public array $formErrorsItem = [
		'element' => 'li',
	];

	public array $controls = [];

	public array $pair = [
		'attributes' => [
			'class' => 'form-group',
		],
		'.required' => [
			'attributes' => [
				'class' => 'required',
			],
		],
		'.button' => [
			'element' => '',
		],
	];

	public array $label = [
		'.required' => [
			'caption' => '%s*',
		],
	];

	public array $control = [
		'attributes' => [
			'class' => 'form-control',
		],
		'separator' => [
			'.checkbox' => [
				'element' => 'div',
				'attributes' => [
					'class' => 'form-check',
				],
			],
		],
		'description' => [
			'attributes' => [
				'class' => 'form-text',
			],
		],
		'error' => [
			'attributes' => [
				'class' => 'invalid-feedback',
			],
		],
		'.valid' => [
			'attributes' => [
				'class' => 'is-valid',
			],
		],
		'.button' => [
			'attributes' => [
				'class=' => 'form-control-submit',
			],
		],
		'.checkbox' => [
			'attributes' => [
				'class=' => 'form-check-input in-form',
			],
		],
		'.invalid' => [
			'attributes' => [
				'class' => 'is-invalid',
			],
		],
	];

	public function __construct(
		private ThemeFormRendererFactory $themeFormRendererFactory,
	)
	{
	}

	public function getTemplateFile(): string
	{
		return __DIR__ . '/templates/form.latte';
	}

	public function createRenderer(): FormRenderer
	{
		return $this->themeFormRendererFactory->create($this);
	}

	/**
	 * @return mixed[]
	 */
	public function getFormErrors(Form $form): array
	{
		if ($form instanceof FormWithOptions) {
			$own = ($form->getOption(self::FormErrors) ?? [])['own'] ?? true;

			if (!$own) {
				return $form->getErrors();
			}
		}

		return $form->getOwnErrors();
	}

	public function getFormErrorsElement(Form $form): HtmlElement
	{
		$element = new HtmlElement();
		$element->merge($this->formErrors);
		$element->addFromFormOption($form, self::FormErrors);

		return $element;
	}

	public function getFormErrorsItemElement(Form $form): HtmlElement
	{
		$element = new HtmlElement();
		$element->merge($this->formErrorsItem);
		$element->addFromFormOption($form, self::FormErrorsItem);

		return $element;
	}

	public function getPairElement(BaseControl $control): HtmlElement
	{
		$specials = [
			'.required' => $control->isRequired(),
			'.optional' => !$control->isRequired(),
			'.invalid' => $this->useInvalidClass($control),
			'.valid' => $this->useValidClass($control),
		];
		$this->addSpecialType($specials, $control);

		$element = new HtmlElement($specials);

		$element->merge($this->pair);
		$element->addFromControlOption($control, self::Pair);

		return $element;
	}

	public function getLabelElement(BaseControl $control): HtmlElement
	{
		$specials = [
			'.required' => $control->isRequired(),
			'.optional' => !$control->isRequired(),
			'.invalid' => $this->useInvalidClass($control),
			'.valid' => $this->useValidClass($control),
		];
		$this->addSpecialType($specials, $control);

		$attributes = new HtmlElement($specials);
		$attributes->merge($this->label);
		$attributes->addFromControlOption($control, self::Label);

		return $attributes;
	}

	public function getControlElement(BaseControl $control): HtmlElement
	{
		$specials = [
			'.required' => $control->isRequired(),
			'.optional' => !$control->isRequired(),
			'.invalid' => $this->useInvalidClass($control),
			'.valid' => $this->useValidClass($control),
		];

		$this->addSpecialType($specials, $control);

		$attributes = new HtmlElement($specials);
		$attributes->merge($this->control);
		$attributes->addFromControlOption($control, self::Control);

		return $attributes;
	}

	public function getDescriptionElement(BaseControl $control): HtmlElement
	{
		return $this->getControlAttributesBySection($control, 'description');
	}

	public function getErrorElement(BaseControl $control): HtmlElement
	{
		return $this->getControlAttributesBySection($control, 'error');
	}

	public function getControlAttributesBySection(BaseControl $control, string $section): HtmlElement
	{
		$specials = [];
		$this->addSpecialType($specials, $control);

		$attributes = new HtmlElement($specials, $section);
		$attributes->merge($this->control);
		$attributes->addFromControlOption($control, 'rendering:' . $section);

		return $attributes;
	}

	public function enterForm(Form $form): void
	{
		$attributes = new HtmlElement();
		$attributes->merge($this->form);

		$attributes->applyToHtml($form->getElementPrototype());
	}

	public function enterControl(BaseControl $control): void
	{
		$this->getControlElement($control)->applyToHtml($control->getControlPrototype());
		$this->getLabelElement($control)->applyToHtml($control->getLabelPrototype());

		if ($control instanceof Checkbox || $control instanceof CheckboxList) {
			$this->getControlAttributesBySection($control, 'container')->applyToHtml($control->getContainerPrototype());
			$this->getControlAttributesBySection($control, 'separator')->applyToHtml($control->getSeparatorPrototype());
		}
	}

	public function getLabelHtml(BaseControl $control): ?Html
	{
		$caption = $control->caption;

		if (!$caption) {
			return null;
		}

		if ($control->isRequired() && $this->asteriskInCaption && is_string($caption) && $caption) {
			$caption .= '*';
		}

		return $control->getLabel($caption);
	}

	/**
	 * @param array<string, bool> $specials
	 * @param BaseControl $control
	 */
	private function addSpecialType(array &$specials, BaseControl $control): void
	{
		if ($type = $control->getOption('type')) {
			$specials['.' . $type] = true;
		}
	}

	private function useValidClass(BaseControl $control): bool
	{
		$form = $control->getForm(false);

		if (!$form || !$form->isSubmitted()) {
			return false;
		}

		return !$control->hasErrors();
	}

	private function useInvalidClass(BaseControl $control): bool
	{
		$form = $control->getForm(false);

		if (!$form || !$form->isSubmitted()) {
			return false;
		}

		return $control->hasErrors();
	}

}
