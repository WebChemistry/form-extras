<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Rule;

use Nette\Forms\Controls\BaseControl;
use Symfony\Component\Validator\Constraint;

interface SymfonyNetteRuleInterface
{

	public function supports(BaseControl $control, Constraint $constraint, array $context = []): bool;

	public function apply(BaseControl $control, Constraint $constraint, array $context = []): void;

}
