<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Theme\Html;

final class HtmlAttributes
{

	/** @var array<string, string[]> */
	private array $attributes = [];

	/** @var array<string, true> */
	private array $unsets = [];

	public function unset(string $name): self
	{
		unset($this->attributes[$name]);

		$this->unsets[$name] = true;

		return $this;
	}

	/**
	 * @param string[]|string $value
	 */
	public function append(string $name, array|string $value): self
	{
		if (!isset($this->attributes[$name])) {
			$this->set($name, $value);
		} else {
			$this->attributes[$name] = array_merge($this->attributes[$name], (array) $value);
		}

		return $this;
	}

	/**
	 * @param string[]|string $value
	 */
	public function set(string $name, array|string $value): self
	{
		$this->attributes[$name] = (array) $value;

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getUnsets(): array
	{
		return array_keys($this->unsets);
	}

	/**
	 * @return array<string, string[]>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function merge(HtmlAttributes $attributes): void
	{
		foreach ($attributes->unsets as $name => $_) {
			$this->unset($name);
		}

		foreach ($attributes->attributes as $name => $values) {
			$this->append($name, $values);
		}
	}

}
