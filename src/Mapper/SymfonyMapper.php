<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Form;
use Nette\Utils\Arrays;
use Nette\Utils\Callback;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

class SymfonyMapper implements MapperInterface
{

	public const IGNORE_CONTROL = 'serializer:ignoreControl';

	/** @var callable[] */
	public array $onValues = [];

	/** @var array<callable(mixed[]): mixed[]> */
	public array $toArrayDecorator = [];

	/** @var array<callable(object): object> */
	public array $toObjectDecorator = [];

	/** @var callable[] */
	public array $onBeforeDenormalization = [];

	/** @var callable[] */
	public array $onAfterDenormalization = [];

	/** @var array<callable(object): void> */
	public array $onBeforeNormalization = [];

	/** @var array<callable(mixed[]): void> */
	public array $onAfterNormalization = [];

	/** @var mixed[] */
	private array $normalizationContext = [];

	/** @var mixed[] */
	private array $denormalizationContext = [];

	/** @var array<int|string, string|array> */
	private array $exportedAttributes = [];

	private object $object;

	public function __construct(
		private Serializer $serializer,
	)
	{
	}

	/**
	 * @param array<int|string, string|array> $exportedAttributes
	 */
	public function setExportedAttributes(array $exportedAttributes): static
	{
		$this->exportedAttributes = $exportedAttributes;

		return $this;
	}

	/**
	 * @param mixed[] $normalizationContext
	 */
	public function addToNormalizationContext(array $normalizationContext): static
	{
		$this->normalizationContext = array_merge($this->normalizationContext, $normalizationContext);

		return $this;
	}

	public function addSingleToNormalizationContext(string $key, mixed $value): static
	{
		$this->normalizationContext[$key] = $value;

		return $this;
	}

	/**
	 * @param mixed[] $normalizationContext
	 */
	public function setNormalizationContext(array $normalizationContext): static
	{
		$this->normalizationContext = $normalizationContext;

		return $this;
	}

	/**
	 * @param mixed[] $denormalizationContext
	 */
	public function addToDenormalizationContext(array $denormalizationContext): static
	{
		$this->denormalizationContext = array_merge($this->denormalizationContext, $denormalizationContext);

		return $this;
	}

	public function addSingleToDenormalizationContext(string $key, mixed $value): static
	{
		$this->denormalizationContext[$key] = $value;

		return $this;
	}

	/**
	 * @param mixed[] $denormalizationContext
	 */
	public function setDenormalizationContext(array $denormalizationContext): static
	{
		$this->denormalizationContext = $denormalizationContext;

		return $this;
	}

	public function mapToObject(Form $form, string $className, array $values, ?object $object): object
	{
		if (!isset($this->object)) {
			$context = $this->denormalizationContext;
			if ($object) {
				$context[AbstractNormalizer::OBJECT_TO_POPULATE] = $object;
			}

			foreach ($this->onBeforeDenormalization as $callback) {
				Callback::check($callback);
				$return = $callback($values);

				if (is_array($return)) {
					$values = $return;
				}
			}

			$this->object = $this->serializer->denormalize($values, $className, context: $context);

			Arrays::invoke($this->onAfterDenormalization);

			foreach ($this->toObjectDecorator as $decorator) {
				$this->object = $decorator($this->object);
			}
		}

		return $this->object;
	}

	public function mapToArray(Form $form, object $object): array
	{
		$context = $this->normalizationContext;

		Arrays::invoke($this->onBeforeNormalization, $object);

		if (!isset($context[AbstractNormalizer::ATTRIBUTES]) && !isset($context[AbstractNormalizer::IGNORED_ATTRIBUTES])) {
			$context[AbstractNormalizer::ATTRIBUTES] = $this->getAttributesForNormalizationContext(
				$form,
				$this->exportedAttributes,
			);
		}

		$array = $this->serializer->normalize($object, context: $context);

		Arrays::invoke($this->onAfterNormalization, $array);

		foreach ($this->toArrayDecorator as $decorator) {
			$array = $decorator($array);
		}

		return $array;
	}

	private function getAttributesForNormalizationContext(Container $container, array $attributes = []): array
	{
		foreach ($container->getComponents() as $component) {
			if ($component instanceof Container) {
				$name = $component->getName();

				$attributes[$name] = $this->getAttributesForNormalizationContext($component, $attributes[$name] ?? []);

				continue;
			}

			if (!$component instanceof BaseControl) {
				continue;
			}

			if ($component instanceof Button) {
				continue;
			}

			if ($component->getOption(self::IGNORE_CONTROL)) {
				continue;
			}

			$attributes[] = $component->getName();
		}

		return $attributes;
	}

}
