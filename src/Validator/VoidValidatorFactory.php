<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

final class VoidValidatorFactory implements ValidatorFactoryInterface
{

	public function create(): VoidValidator
	{
		return new VoidValidator();
	}

}
