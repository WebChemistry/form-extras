<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

use WebChemistry\FormExtras\Form;

final class VoidValidator implements ValidatorInterface
{

	private static VoidValidator $instance;

	public function setValidate(bool $validate): static
	{
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function validate(Form $form, object|array $values): void
	{
	}

	public static function getInstance(): VoidValidator
	{
		return self::$instance ??= new self();
	}

}
