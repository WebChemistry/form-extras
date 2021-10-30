<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

use WebChemistry\FormExtras\Form;

interface ValidatorInterface
{

	public function setValidate(bool $validate): static;

	public function validate(Form $form, array|object $values): void;

}
