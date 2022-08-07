<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Factory;

use WebChemistry\FormExtras\Form;

interface FormFactory
{

	public function create(string $mappedType = 'array'): Form;

}
