<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

interface ValidatorFactoryInterface
{

	public function create(): ValidatorInterface;

}
