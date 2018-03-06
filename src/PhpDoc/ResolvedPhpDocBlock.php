<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDoc\Tag\GenericTag;
use PHPStan\PhpDoc\Tag\InheritTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Type\ResolvableGenericType;

class ResolvedPhpDocBlock
{

	/**
	 * @var \PHPStan\PhpDoc\Tag\VarTag[]
	 */
	private $varTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\MethodTag[]
	 */
	private $methodTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\PropertyTag[]
	 */
	private $propertyTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\ParamTag[]
	 */
	private $paramTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\GenericTag[]
	 */
	private $genericTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\InheritTag[]
	 */
	private $inheritTags;

	/**
	 * @var \PHPStan\PhpDoc\Tag\ReturnTag|null
	 */
	private $returnTag;


	/**
	 * @param \PHPStan\PhpDoc\Tag\VarTag[] $varTags
	 * @param \PHPStan\PhpDoc\Tag\MethodTag[] $methodTags
	 * @param \PHPStan\PhpDoc\Tag\PropertyTag[] $propertyTags
	 * @param \PHPStan\PhpDoc\Tag\ParamTag[] $paramTags
	 * @param \PHPStan\PhpDoc\Tag\GenericTag[] $genericTags
	 * @param \PHPStan\PhpDoc\Tag\InheritTag[] $inheritTags
	 * @param \PHPStan\PhpDoc\Tag\ReturnTag|null $returnTag
	 */
	private function __construct(
		array $varTags,
		array $methodTags,
		array $propertyTags,
		array $paramTags,
		array $genericTags,
		array $inheritTags,
		?ReturnTag $returnTag
	)
	{
		$this->varTags = $varTags;
		$this->methodTags = $methodTags;
		$this->propertyTags = $propertyTags;
		$this->paramTags = $paramTags;
		$this->genericTags = $genericTags;
		$this->inheritTags = $inheritTags;
		$this->returnTag = $returnTag;
	}

	/**
	 * @param \PHPStan\PhpDoc\Tag\VarTag[] $varTags
	 * @param \PHPStan\PhpDoc\Tag\MethodTag[] $methodTags
	 * @param \PHPStan\PhpDoc\Tag\PropertyTag[] $propertyTags
	 * @param \PHPStan\PhpDoc\Tag\ParamTag[] $paramTags
	 * @param \PHPStan\PhpDoc\Tag\GenericTag[] $genericTags
	 * @param \PHPStan\PhpDoc\Tag\InheritTag[] $inheritTags
	 * @param \PHPStan\PhpDoc\Tag\ReturnTag|null $returnTag
	 * @return self
	 */
	public static function create(
		array $varTags,
		array $methodTags,
		array $propertyTags,
		array $paramTags,
		array $genericTags,
		array $inheritTags,
		?ReturnTag $returnTag
	): self
	{
		return new self($varTags, $methodTags, $propertyTags, $paramTags, $genericTags, $inheritTags, $returnTag);
	}

	public static function createEmpty(): self
	{
		return new self([], [], [], [], [], [], null);
	}


	/**
	 * @return \PHPStan\PhpDoc\Tag\VarTag[]
	 */
	public function getVarTags(): array
	{
		return $this->varTags;
	}

	/**
	 * @return \PHPStan\PhpDoc\Tag\MethodTag[]
	 */
	public function getMethodTags(): array
	{
		return $this->methodTags;
	}

	/**
	 * @return \PHPStan\PhpDoc\Tag\PropertyTag[]
	 */
	public function getPropertyTags(): array
	{
		return $this->propertyTags;
	}

	/**
	 * @return \PHPStan\PhpDoc\Tag\ParamTag[]
	 */
	public function getParamTags(): array
	{
		return $this->paramTags;
	}


	/**
	 * @return \PHPStan\PhpDoc\Tag\GenericTag[]
	 */
	public function getGenericTags(): array
	{
		return $this->genericTags;
	}


	/**
	 * @return \PHPStan\PhpDoc\Tag\InheritTag[]
	 */
	public function getInheritTags(): array
	{
		return $this->inheritTags;
	}


	public function getReturnTag(): ?\PHPStan\PhpDoc\Tag\ReturnTag
	{
		return $this->returnTag;
	}


	public function resolveGenericTypes(array $genericTypesMap): self
	{
		$varTags = $this->varTags;
		foreach ($this->varTags as $varName => $varTag) {
			$type = $varTag->getType();
			if ($type instanceof ResolvableGenericType) {
				$varTags[$varName] = new VarTag($type->resolveGenericType($genericTypesMap));
			}
		}

		$methodTags = $this->methodTags;
		foreach ($methodTags as $methodName => $methodTag) {
			$parameters = $methodTag->getParameters();
			foreach ($parameters as $parameterName => $parameterTag) {
				$type = $parameterTag->getType();
				if ($type instanceof ResolvableGenericType) {
					$parameters[$parameterName] = new MethodTagParameter(
						$type->resolveGenericType($genericTypesMap),
						$parameterTag->isPassedByReference(),
						$parameterTag->isOptional(),
						$parameterTag->isVariadic()
					);
				}
			}
			$type = $methodTag->getReturnType();
			if ($type instanceof ResolvableGenericType) {
				$type = $type->resolveGenericType($genericTypesMap);
			}
			$methodTags[$methodName] = new MethodTag($type, $methodTag->isStatic(), $parameters);
		}

		$propertyTags = $this->propertyTags;
		foreach ($propertyTags as $propertyName => $propertyTag) {
			$type = $propertyTag->getType();
			if ($type instanceof ResolvableGenericType) {
				$propertyTags[$propertyName] = new PropertyTag(
					$type->resolveGenericType($genericTypesMap),
					$propertyTag->isReadable(),
					$propertyTag->isWritable()
				);
			}
		}

		$paramTags = $this->paramTags;
		foreach ($paramTags as $paramName => $paramTag) {
			$type = $paramTag->getType();
			if ($type instanceof ResolvableGenericType) {
				$paramTags[$paramName] = new ParamTag($type->resolveGenericType($genericTypesMap), $paramTag->isVariadic());
			}
		}

		$genericTags = $this->genericTags;
		foreach ($genericTags as $genericName => $genericTag) {
			$type = $genericTag->getConstraint();
			if ($type instanceof ResolvableGenericType) {
				$genericTags[$genericName] = new GenericTag(
					$type->resolveGenericType($genericTypesMap),
					$genericTag->getConstraintType(),
					$genericTag->getVarianceType()
				);
			}
		}

		$inheritTags = $this->inheritTags;
		foreach ($inheritTags as $classType => $inheritTag) {
			$types = $inheritTag->getGenericTypes();
			foreach ($types as $i => $type) {
				if ($type instanceof ResolvableGenericType) {
					$types[$i] = $type->resolveGenericType($genericTypesMap);
				}
			}
			$inheritTags[$classType] = new InheritTag($inheritTag->getInheritanceType(), $types);
		}

		$returnTag = $this->returnTag;
		if ($returnTag !== null) {
			$type = $returnTag->getType();
			if ($type instanceof ResolvableGenericType) {
				$returnTag = new ReturnTag($type->resolveGenericType($genericTypesMap));
			}
		}

		return self::create(
			$varTags,
			$methodTags,
			$propertyTags,
			$paramTags,
			$genericTags,
			$inheritTags,
			$returnTag
		);
	}


	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['varTags'],
			$properties['methodTags'],
			$properties['propertyTags'],
			$properties['paramTags'],
			$properties['genericTags'],
			$properties['inheritTags'],
			$properties['returnTag']
		);
	}

}
