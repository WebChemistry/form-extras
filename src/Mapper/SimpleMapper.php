<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

use Nette\Forms\Form;
use ReflectionClass;
use stdClass;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use WebChemistry\FormExtras\Mapper\Exceptions\NoConstructorValueException;

final class SimpleMapper implements MapperInterface
{

	private static SimpleMapper $instance;

	private PropertyAccessor $propertyAccessor;

	public function __construct(
		?PropertyAccessor $propertyAccessor = null,
	)
	{
		$this->propertyAccessor = $propertyAccessor ?? $this->createPropertyAccessor();
	}

	public function mapToObject(Form $form, string $className, array $values, ?object $object = null): object
	{
		if (!$object) {
			$reflection = new ReflectionClass($className);
			$constructor = $reflection->getConstructor();

			if ($constructor && $constructor->getNumberOfParameters() !== 0) {
				$arguments = [];
				foreach ($constructor->getParameters() as $parameter) {
					if (!array_key_exists($name = $parameter->getName(), $values)) {
						if ($parameter->isDefaultValueAvailable()) {
							$arguments[] = $parameter->getDefaultValue();

							continue;
						}

						throw new NoConstructorValueException(sprintf('Constructor value "%s" not exists.', $name));
					}

					$arguments[] = $values[$name];

					unset($values[$name]);
				}

				$object = $reflection->newInstanceArgs($arguments);
			} else {
				$object = $reflection->newInstance();
			}
		}

		foreach ($values as $property => $value) {
			if ($object instanceof stdClass) {
				$object->$property = $value;
			} else {
				$this->propertyAccessor->setValue($object, $property, $value);
			}
		}

		return $object;
	}

	public function mapToArray(Form $form, object $object): array
	{
		$properties = $this->getProperties($object);

		$values = [];
		foreach ($properties as $property) {
			if ($this->propertyAccessor->isReadable($object, $property)) {
				$values[$property] = $this->propertyAccessor->getValue($object, $property);
			}
		}

		return $values;
	}

	private function getProperties(object $object): array
	{
		$properties = [];
		foreach ((array) $object as $property => $_) {
			$pos = strrpos($property, "\0");

			if ($pos !== false) {
				$properties[] = substr($property, $pos + 1);
			} else {
				$properties[] = $property;
			}
		}

		return $properties;
	}

	private function createPropertyAccessor(): PropertyAccessorInterface
	{
		return PropertyAccess::createPropertyAccessor();
	}

	public static function getInstance(): self
	{
		return self::$instance ??= new self();
	}

}
