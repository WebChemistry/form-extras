<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

use Nette\Forms\Form;

interface MapperInterface
{

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param mixed[] $values
	 * @param T|null $object
	 * @return T
	 */
	public function mapToObject(Form $form, string $className, array $values, ?object $object): object;

	/**
	 * @param object $object
	 * @return mixed[]
	 */
	public function mapToArray(Form $form, object $object): array;

}
