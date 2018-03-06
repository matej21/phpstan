<?php declare(strict_types = 1);

namespace PHPStan\Reflection;


class GenericClassReflection
{
	/** @var GenericTypeReflection[] */
	private $genericTypesReflection;

	/** @var InheritanceReflection[] */
	private $inheritanceReflection;


	public function __construct(array $genericTypesReflection, array $inheritanceReflection)
	{
		$this->genericTypesReflection = $genericTypesReflection;
		$this->inheritanceReflection = $inheritanceReflection;
	}


	/**
	 * @return GenericTypeReflection[]
	 */
	public function getGenericTypes(): array
	{
		return $this->genericTypesReflection;
	}


	/**
	 * @return InheritanceReflection[]
	 */
	public function getInheritances(): array
	{
		return $this->inheritanceReflection;
	}
}
