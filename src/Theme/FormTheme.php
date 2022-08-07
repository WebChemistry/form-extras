<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Theme;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Forms\FormRenderer;
use WebChemistry\FormExtras\Theme\Html\HtmlElement;

interface FormTheme
{

	public const FormErrors = 'rendering:errors';
	public const FormErrorsItem = 'rendering:errors:item';
	public const Pair = 'rendering:pair';
	public const Label = 'rendering:label';
	public const Control = 'renderin:control';
	public const ControlContainer = 'rendering:container';
	public const ControlSeparator = 'rendering:separator';

	public function getTemplateFile(): string;

	public function createRenderer(): FormRenderer;

	/**
	 * @return mixed[]
	 */
	public function getFormErrors(Form $form): array;

	public function getFormErrorsElement(Form $form): HtmlElement;

	public function getFormErrorsItemElement(Form $form): HtmlElement;

	public function getPairElement(BaseControl $control): HtmlElement;

	public function getLabelElement(BaseControl $control): HtmlElement;

	public function getControlElement(BaseControl $control): HtmlElement;

	public function getDescriptionElement(BaseControl $control): HtmlElement;

	public function getErrorElement(BaseControl $control): HtmlElement;

	public function getControlAttributesBySection(BaseControl $control, string $section): HtmlElement;

	public function enterForm(Form $form): void;

	public function enterControl(BaseControl $control): void;

}
