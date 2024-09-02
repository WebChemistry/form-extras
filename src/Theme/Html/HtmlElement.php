<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Theme\Html;

use LogicException;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Nette\Utils\Html;
use WebChemistry\FormExtras\Extension\FormWithOptions;

final class HtmlElement
{

	private ?string $name = null;

	private HtmlAttributes $attributes;

	/** @var string[] */
	private array $specials;

	/**
	 * @param array<string, bool> $specials
	 */
	public function __construct(
		array $specials = [],
		private ?string $section = null,
	)
	{
		$this->attributes = new HtmlAttributes();
		$this->specials = array_filter($specials);
	}

	public function addFromControlOption(BaseControl $control, string $name): self
	{
		$values = $control->getOption($name) ?? [];
		if (!is_array($values)) {
			throw new LogicException(
				sprintf('Option "%s" must be an array in form control "%s".', $name, $control->getName())
			);
		}

		return $this->merge($values);
	}

	public function addFromFormOption(Form $form, string $name): self
	{
		if (!$form instanceof FormWithOptions) {
			return $this;
		}


		$values = $form->getOption($name) ?? [];
		if (!is_array($values)) {
			throw new LogicException(
				sprintf('Option "%s" must be an array in form "%s".', $name, $form->getName())
			);
		}

		return $this->merge($values);
	}

	/**
	 * @param array<string, string|array<string|null>|null> $values
	 */
	public function merge(array $values): self
	{
		if ($this->section) {
			$values = $values[$this->section] ?? [];
		}

		$this->doMerge($values, $attributes = new HtmlAttributes());

		$this->attributes->merge($attributes);

		return $this;
	}

	/**
	 * @param array<string, string|array<string|null>|null> $values
	 */
	public function doMerge(array $values, HtmlAttributes $attributes): void
	{
		if (!$values) {
			return;
		}

		foreach ($values as $sectionName => $sectionValues) {
			if ($sectionName === 'attributes') {
				foreach ($sectionValues as $name => $value) {
					if (str_ends_with($name, '!')) {
						$attributes->unset($name = substr($name, 0, -1));
						$attributes->set($name, $value);

					} elseif (str_ends_with($name, '=')) {
						$attributes->set(substr($name, 0, -1), $value);

					} else {
						$attributes->append($name, $value);

					}
				}
			}

			if ($sectionName === 'element') {
				$this->name = $sectionValues;
			}

			if (isset($this->specials[$sectionName])) {
				$this->doMerge($sectionValues, $attributes);
			}
		}
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function getAttributes(): HtmlAttributes
	{
		return $this->attributes;
	}

	/**
	 * @return array<string, string[]>
	 */
	public function getAttributesAsArray(): array
	{
		return $this->attributes->getAttributes();
	}

	public function applyToHtml(Html $html): void
	{
		if ($this->name) {
			$html->setName($this->name);
		}

		foreach ($this->attributes->getUnsets() as $name) {
			$html->removeAttribute($name);
		}

		foreach ($this->attributes->getAttributes() as $name => $strings) {
			if (str_starts_with($name, 'data-')) {
				$html->setAttribute($name, implode(' ', $strings));
			} else {
				foreach ($strings as $string) {
					$html->appendAttribute($name, $string);
				}
			}
		}
	}

}
