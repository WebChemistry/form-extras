<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Rule;

use Nette\Forms\Form;

interface RuleApplierInterface
{

	/**
	 * @param mixed[] $context
	 */
	public function apply(string $className, Form $form, array $context = []): void;

}
