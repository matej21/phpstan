<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;

class UnresolvedGenericType extends MixedType implements ResolvableGenericType
{

	/** @var string */
	private $className;

	/** @var string */
	private $genericTypeName;

	/** @var null|Type */
	private $constraint;


	public function __construct(string $className, string $genericTypeName, ?Type $constraint = null)
	{
		parent::__construct(false);
		$this->className = $className;
		$this->genericTypeName = $genericTypeName;
		$this->constraint = $constraint;
	}


	public function accepts(Type $type): bool
	{
		if ($type instanceof UnresolvedGenericType) {
			//todo class must be also checked
			if ($type->genericTypeName === $this->genericTypeName) {
				return true;
			}
			$constraint = $type->getConstraint();
			return $constraint ? $this->accepts($constraint) : false;
		}
		return parent::accepts($type);
	}


	public function getGenericTypeName(): string
	{
		return $this->genericTypeName;
	}


	public function getConstraint(): ?Type
	{
		return $this->constraint;
	}


	/**
	 * @param array $genericTypesMap
	 * @return Type
	 */
	public function resolveGenericType(array $genericTypesMap): Type
	{
		return $genericTypesMap[$this->genericTypeName] ?? $this;
	}


	public static function __set_state(array $properties): Type
	{
		return new self($properties['className'], $properties['genericTypeName']);
	}


	public function describe(): string
	{
		return '<' . $this->genericTypeName . '>';
	}

}
