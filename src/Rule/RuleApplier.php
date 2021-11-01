<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Rule;

use LogicException;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Form;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RuleApplier implements RuleApplierInterface
{

	public const SKIP_FIELDS = 'skipFields';
	public const CUSTOM_FIELD_MAPPING = 'customMapping';

	/**
	 * @param SymfonyNetteRuleInterface[] $rules
	 */
	public function __construct(
		private array $rules,
		private ValidatorInterface $validator
	)
	{
	}

	/**
	 * @param mixed[] $context
	 */
	public function apply(string $className, Form $form, array $context = []): void
	{
		$this->applyToContainer(
			$this->getMetadata($className),
			$form,
			$context[self::CUSTOM_FIELD_MAPPING] ?? [],
			$context[self::SKIP_FIELDS] ?? [],
			$context,
		);
	}

	/**
	 * @param mixed[] $context
	 * @param mixed[] $customFieldMapping
	 * @param mixed[] $skipFields
	 */
	private function applyToContainer(
		ClassMetadata $metadata,
		Container $container,
		array $customFieldMapping,
		array $skipFields,
		array $context,
	): void
	{
		foreach ($container->getComponents() as $component) {
			$field = $component->getName();

			if (in_array($field, $skipFields, true)) {
				continue;
			}

			if ($component instanceof Container) {
				continue; // TODO
			}

			if (!$component instanceof BaseControl) {
				continue;
			}

			if ($component instanceof Button) {
				continue;
			}

			$field = $customFieldMapping[$field] ?? $field;

			if (!$metadata->hasPropertyMetadata($field)) {
				continue;
			}

			foreach ($metadata->getPropertyMetadata($field) as $propertyMetadata) {
				foreach ($propertyMetadata->getConstraints() as $constraint) {
					foreach ($this->rules as $rule) {
						if ($rule->supports($component, $constraint, $context)) {
							$rule->apply($component, $constraint, $context);

							break;
						}
					}
				}
			}
		}
	}

	private function getMetadata(string $className): ClassMetadata
	{
		$metadata = $this->validator->getMetadataFor($className);
		if (!$metadata instanceof ClassMetadata) {
			throw new LogicException(
				sprintf('Expected metadata %s, %s given.', ClassMetadata::class, get_debug_type($metadata))
			);
		}

		return $metadata;
	}

}
