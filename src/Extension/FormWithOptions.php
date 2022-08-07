<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Extension;

interface FormWithOptions
{

	public function setOption(string $name, mixed $value): static;

	public function getOption(string $name, mixed $default = null): mixed;

}
