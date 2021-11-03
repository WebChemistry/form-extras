<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Rule;

use Nette\Forms\Form;

class CompositeFormRuleApplier implements FormRuleApplierInterface
{

	/**
	 * @param FormRuleApplierInterface[] $appliers
	 */
	public function __construct(
		private array $appliers,
	)
	{
	}

	/**
	 * @param mixed[] $context
	 */
	public function apply(string $className, Form $form, array $context = []): void
	{
		foreach ($this->appliers as $applier) {
			$applier->apply($className, $form, $context);
		}
	}

}
