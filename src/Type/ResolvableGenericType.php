<?php declare(strict_types = 1);

namespace PHPStan\Type;

interface ResolvableGenericType
{

	/**
	 * @param Type[] $genericTypesMap
	 * @return Type
	 */
	public function resolveGenericType(array $genericTypesMap): Type;

}
