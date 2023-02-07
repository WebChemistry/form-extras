<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

final class SymfonyValidatorFactory implements ValidatorFactoryInterface
{

	public function __construct(
		private SymfonyValidatorInterface $validator,
	)
	{
	}

	public function create(): SymfonyValidator
	{
		return new SymfonyValidator($this->validator);
	}

}
