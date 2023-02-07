<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

use Symfony\Component\Serializer\Serializer;

final class SymfonyMapperFactory implements MapperFactoryInterface
{

	public function __construct(
		private Serializer $serializer,
	)
	{
	}

	public function create(): SymfonyMapper
	{
		return new SymfonyMapper($this->serializer);
	}

}
