<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras;

use DomainException;
use Nette\Application\UI\Form as NetteUIForm;
use Nette\Localization\Translator;
use WebChemistry\FormExtras\Extension\FormWithOptions;
use WebChemistry\FormExtras\Mapper\MapperInterface;
use WebChemistry\FormExtras\Mapper\SimpleMapper;
use WebChemistry\FormExtras\Theme\FormTheme;
use WebChemistry\FormExtras\Validator\ValidatorInterface;
use WebChemistry\FormExtras\Validator\VoidValidator;

class Form extends NetteUIForm implements FormWithOptions
{

	private FormTheme $theme;

	private MapperInterface $mapper;

	private ValidatorInterface $validator;

	private bool $validatorRegistered = false;

	private object|array|null $defaults = null;

	/** @var mixed[] */
	private array $fixedValues = [];

	/** @var mixed[] */
	private array $options = [];

	public function __construct(
		private string $mappedType = 'array',
		?ValidatorInterface $validator = null,
		?MapperInterface $mapper = null,
		?Translator $translator = null,
		?FormTheme $theme = null,
	)
	{
		parent::__construct();

		$this->validator = $validator ?? VoidValidator::getInstance();
		$this->mapper = $mapper ?? SimpleMapper::getInstance();

		if ($theme) {
			$this->theme = $theme;
			$this->setRenderer($theme->createRenderer());
		}

		$this->setTranslator($translator);
	}

	public function hasTheme(): bool
	{
		return isset($this->theme);
	}

	public function getTheme(): FormTheme
	{
		return $this->theme ?? throw new DomainException(sprintf('Theme is not set for "%s" form.', $this->getName()));
	}

	public function setTheme(?FormTheme $theme): static
	{
		$this->theme = $theme;

		return $this;
	}

	public function getValidator(): ValidatorInterface
	{
		return $this->validator;
	}

	public function getMapper(): MapperInterface
	{
		return $this->mapper;
	}

	public function addFixedValue(string $name, mixed $value): static
	{
		$this->fixedValues[$name] = $value;

		return $this;
	}

	public function setMappedType(string $type): static
	{
		$this->mappedType = $type;

		return $this;
	}

	/**
	 * @param mixed[]|object|null $data
	 */
	public function setDefaults($data, bool $erase = false): static
	{
		if ($data === null) {
			return $this;
		}

		return parent::setDefaults($this->defaults = $data, $erase);
	}

	public function hasDefaults(): bool
	{
		return $this->defaults !== null;
	}

	public function getDefaults(): object|array|null
	{
		return $this->defaults;
	}

	public function getArrayDefaults(): array
	{
		if (is_object($this->defaults)) {
			return $this->mapper->mapToArray($this, $this->defaults);
		}

		return (array) $this->defaults;
	}

	/**
	 * @inheritDoc
	 */
	public function setValues($data, bool $erase = false)
	{
		return parent::setValues(
			is_object($data) ? $this->mapper->mapToArray($this, $data) : $data,
			$erase
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getUnsafeValues($returnType = null, array $controls = null)
	{
		$array = parent::getUnsafeValues('array', $controls);
		$type = $returnType ?? $this->mappedType;

		$array = array_merge($this->fixedValues, $array);

		if (!$type || $type === 'array') {
			return $array;
		}

		return $this->mapper->mapToObject($this, $type, $array, is_object($this->defaults) ? $this->defaults : null);
	}

	public function validate(array $controls = null): void
	{
		if (!$this->validatorRegistered) {
			$this->validatorRegistered = true;

			$this->onValidate[] = function (): void {
				$this->validator->validate($this, $this->getUnsafeValues());
			};
		}

		parent::validate($controls);
	}

	public function setOption(string $name, mixed $value): static
	{
		$this->options[$name] = $value;

		return $this;
	}

	public function getOption(string $name, mixed $default = null): mixed
	{
		return $this->options[$name] ?? $default;
	}

}
