<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;


class GenericObjectType implements TypeWithClassName
{
	/** @var TypeWithClassName */
	private $mainType;

	/** @var Type[] */
	private $genericTypes;


	/**
	 * @param Type[] $genericTypes
	 */
	public function __construct(TypeWithClassName $mainType, array $genericTypes)
	{
		$this->mainType = $mainType;
		$this->genericTypes = $genericTypes;
	}


	public function getReferencedClasses(): array
	{
		return $this->mainType->getReferencedClasses();
	}


	public function accepts(Type $type): bool
	{
		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this);
		}
		$mainResult = $this->mainType->accepts($type);
		if (!$mainResult) {
			return false;
		}
		if (!$type instanceof GenericObjectType) {
			return false;
		}
		$broker = Broker::getInstance();
		$class = $broker->getClass($this->mainType->getClassName());
		$genericTypes = $class->getGenericTypeReflections();
		foreach ($genericTypes as $i => $typeReflection) {
			$thisType = $this->genericTypes[$i] ?? new MixedType();
			$thatType = $type->genericTypes[$i] ?? new MixedType();

			if ($typeReflection->isCovariant() && !$thisType->accepts($thatType)) {
				return false;
			} elseif ($typeReflection->isContravariant() && !$thatType->accepts($thisType)) {
				return false;
			} elseif ($typeReflection->isInvariant() && (!$thisType->accepts($thatType)  || !$thatType->accepts($thisType))) {
				return false;
			}
		}
		return true;
	}


	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}
		$result = $this->mainType->isSuperTypeOf($type);
		if ($result->no()) {
			return $result;
		}

		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof TypeWithClassName && !$type instanceof GenericObjectType) {
			return TrinaryLogic::createMaybe();
		}

		$broker = Broker::getInstance();
		$class = $broker->getClass($this->mainType->getClassName());
		$genericTypes = $class->getGenericTypeReflections();
		foreach ($genericTypes as $i => $typeReflection) {
			$thisType = $this->genericTypes[$i] ?? null;
			$thatType = $type instanceof GenericObjectType && isset($type->genericTypes[$i]) ? $type->genericTypes[$i] : null;
			if (!$thisType || !$thatType) {
				$result = $result->and(TrinaryLogic::createMaybe());
				continue;
			}

			if ($typeReflection->isCovariant()) {
				$result = $result->and($thisType->isSuperTypeOf($thatType));
			} elseif ($typeReflection->isContravariant()) {
				$result = $result->and($thatType->isSuperTypeOf($thisType));
			} elseif ($typeReflection->isInvariant()) {
				$result = $result->and($thisType->isSuperTypeOf($thatType), $thatType->isSuperTypeOf($thisType));
			} else {
				throw new \LogicException();
			}
			if ($result->no()) {
				return $result;
			}
		}
		return $result;
	}


	public function describe(): string
	{
		$mainTypeDescription = $this->mainType->describe();
		$genericDescriptions = [];
		foreach ($this->genericTypes as $type) {
			$genericDescriptions[] = $type->describe();
		}
		return sprintf('%s<%s>', $mainTypeDescription, implode(', ', $genericDescriptions));
	}


	public function canAccessProperties(): TrinaryLogic
	{
		return $this->mainType->canAccessProperties();
	}


	public function hasProperty(string $propertyName): bool
	{
		return $this->mainType->hasProperty($propertyName);
	}


	public function getProperty(string $propertyName, Scope $scope): PropertyReflection
	{
		return $this->mainType->getProperty($propertyName, $scope);
	}


	public function canCallMethods(): TrinaryLogic
	{
		return $this->mainType->canCallMethods();
	}


	public function hasMethod(string $methodName): bool
	{
		$broker = Broker::getInstance();
		if (!$broker->hasClass($this->getClassName())) {
			return false;
		}

		return $broker->getClass($this->getClassName(), $this->genericTypes)->hasMethod($methodName);
	}


	public function getMethod(string $methodName, Scope $scope): MethodReflection
	{
		$broker = Broker::getInstance();
		return $broker->getClass($this->className, $this->genericTypes)->getMethod($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->mainType->canAccessConstants();
	}


	public function hasConstant(string $constantName): bool
	{
		return $this->mainType->hasConstant($constantName);
	}


	public function getConstant(string $constantName): ClassConstantReflection
	{
		return $this->mainType->getConstant($constantName);
	}


	public function isIterable(): TrinaryLogic
	{
		return $this->mainType->isIterable();
	}


	public function getIterableKeyType(): Type
	{
		return $this->mainType->getIterableKeyType();
	}


	public function getIterableValueType(): Type
	{
		return $this->mainType->getIterableValueType();
	}


	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->mainType->isOffsetAccessible();
	}


	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->mainType->getOffsetValueType($offsetType);
	}


	public function setOffsetValueType(?Type $offsetType, Type $valueType): Type
	{
		return $this->mainType->setOffsetValueType($offsetType, $valueType);
	}


	public function isCallable(): TrinaryLogic
	{
		return $this->mainType->isCallable();
	}


	public function isCloneable(): TrinaryLogic
	{
		return $this->mainType->isCloneable();
	}


	public static function __set_state(array $properties): Type
	{
		return new self($properties['mainType'], $properties['genericTypes']);
	}


	public function getClassName(): string
	{
		return $this->mainType->getClassName();
	}


	public function getMainType(): TypeWithClassName
	{
		return $this->mainType;
	}


	/**
	 * @return Type[]
	 */
	public function getGenericTypes(): array
	{
		return $this->genericTypes;
	}
}
