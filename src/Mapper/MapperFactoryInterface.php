<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Mapper;

interface MapperFactoryInterface
{

	public function create(): MapperInterface;

}
