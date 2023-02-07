<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class SimpleMapperFactory implements MapperFactoryInterface
{

	public function __construct(
		private ?PropertyAccessorInterface $propertyAccessor = null,
	)
	{
	}

	public function create(): SimpleMapper
	{
		return new SimpleMapper($this->propertyAccessor);
	}

}
