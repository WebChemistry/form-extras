<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras;

use Nette\Application\UI\Form as UIForm;
use WebChemistry\FormExtras\Mapper\MapperInterface;
use WebChemistry\FormExtras\Mapper\SimpleMapper;
use WebChemistry\FormExtras\Validator\ValidatorInterface;
use WebChemistry\FormExtras\Validator\VoidValidator;

class Form extends UIForm
{

	private ?string $mappedType = null;

	private MapperInterface $mapper;

	private ValidatorInterface $validator;

	private bool $validatorRegistered = false;

	private object|array|null $defaults = null;

	/** @var mixed[] */
	private array $fixedValues = [];

	public function getValidator(): ValidatorInterface
	{
		return $this->validator ??= VoidValidator::getInstance();
	}

	public function getMapper(): MapperInterface
	{
		return $this->mapper ??= SimpleMapper::getInstance();
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

	/**
	 * @inheritDoc
	 */
	public function setValues($data, bool $erase = false)
	{
		return parent::setValues(
			is_object($data) ? $this->getMapper()->mapToArray($this, $data) : $data,
			$erase
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getUnsafeValues($returnType = null, array $controls = null)
	{
		$array = parent::getUnsafeValues('array', $controls);
		$type = $returnType ?? $this->mappedType ?? null;

		$array = array_merge($this->fixedValues, $array);

		if (!$type || $type === 'array') {
			return $array;
		}

		return $this->getMapper()->mapToObject($this, $type, $array, is_object($this->defaults) ? $this->defaults : null);
	}

	public function validate(array $controls = null): void
	{
		if (!$this->validatorRegistered) {
			$this->validatorRegistered = true;

			$this->onValidate[] = function (): void {
				$this->getValidator()->validate($this, $this->getUnsafeValues());
			};
		}

		parent::validate($controls);
	}

}
