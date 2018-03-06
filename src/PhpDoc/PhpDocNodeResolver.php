<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\Tag\GenericTag;
use PHPStan\PhpDoc\Tag\InheritTag;
use PHPStan\PhpDoc\Tag\MethodTag;
use PHPStan\PhpDoc\Tag\MethodTagParameter;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\PhpDoc\Tag\ReturnTag;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;

class PhpDocNodeResolver
{

	/** @var TypeNodeResolver */
	private $typeNodeResolver;

	public function __construct(TypeNodeResolver $typeNodeResolver)
	{
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function resolve(PhpDocNode $phpDocNode, NameScope $nameScope): ResolvedPhpDocBlock
	{
		return ResolvedPhpDocBlock::create(
			$this->resolveVarTags($phpDocNode, $nameScope),
			$this->resolveMethodTags($phpDocNode, $nameScope),
			$this->resolvePropertyTags($phpDocNode, $nameScope),
			$this->resolveParamTags($phpDocNode, $nameScope),
			$this->resolveGenericTag($phpDocNode, $nameScope),
			$this->resolveInheritTag($phpDocNode, $nameScope),
			$this->resolveReturnTag($phpDocNode, $nameScope)
		);
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return \PHPStan\PhpDoc\Tag\VarTag[]
	 */
	private function resolveVarTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		foreach ($phpDocNode->getVarTagValues() as $tagValue) {
			if ($tagValue->variableName !== '') {
				$variableName = substr($tagValue->variableName, 1);
				$type = !isset($resolved[$variableName])
					? $this->typeNodeResolver->resolve($tagValue->type, $nameScope)
					: new MixedType();
				$resolved[$variableName] = new VarTag($type);

			} else {
				$resolved[] = new VarTag($this->typeNodeResolver->resolve($tagValue->type, $nameScope));
			}
		}

		return $resolved;
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return \PHPStan\PhpDoc\Tag\PropertyTag[]
	 */
	private function resolvePropertyTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach ($phpDocNode->getPropertyTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = !isset($resolved[$propertyName])
				? $this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				: new MixedType();

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				true,
				true
			);
		}

		foreach ($phpDocNode->getPropertyReadTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = !isset($resolved[$propertyName])
				? $this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				: new MixedType();

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				true,
				false
			);
		}

		foreach ($phpDocNode->getPropertyWriteTagValues() as $tagValue) {
			$propertyName = substr($tagValue->propertyName, 1);
			$propertyType = !isset($resolved[$propertyName])
				? $this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				: new MixedType();

			$resolved[$propertyName] = new PropertyTag(
				$propertyType,
				false,
				true
			);
		}

		return $resolved;
	}

	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope $nameScope
	 * @return \PHPStan\PhpDoc\Tag\MethodTag[]
	 */
	private function resolveMethodTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];

		foreach ($phpDocNode->getMethodTagValues() as $tagValue) {
			$parameters = [];
			foreach ($tagValue->parameters as $parameterNode) {
				$parameterName = substr($parameterNode->parameterName, 1);
				$type = $parameterNode->type !== null ? $this->typeNodeResolver->resolve($parameterNode->type, $nameScope) : new MixedType();
				if ($parameterNode->defaultValue instanceof ConstExprNullNode) {
					$type = TypeCombinator::addNull($type);
				}
				$parameters[$parameterName] = new MethodTagParameter(
					$type,
					$parameterNode->isReference,
					$parameterNode->defaultValue !== null,
					$parameterNode->isVariadic
				);
			}

			$resolved[$tagValue->methodName] = new MethodTag(
				$tagValue->returnType !== null ? $this->typeNodeResolver->resolve($tagValue->returnType, $nameScope) : new MixedType(),
				$tagValue->isStatic,
				$parameters
			);
		}

		return $resolved;
	}

	/**
	 * @param  PhpDocNode $phpDocNode
	 * @param  NameScope $nameScope
	 * @return \PHPStan\PhpDoc\Tag\ParamTag[]
	 */
	private function resolveParamTags(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		foreach ($phpDocNode->getParamTagValues() as $tagValue) {
			$parameterName = substr($tagValue->parameterName, 1);
			$parameterType = !isset($resolved[$parameterName])
				? $this->typeNodeResolver->resolve($tagValue->type, $nameScope)
				: new MixedType();

			if ($tagValue->isVariadic) {
				if (!$parameterType instanceof ArrayType) {
					$parameterType = new ArrayType(new IntegerType(), $parameterType);

				} elseif ($parameterType->getIterableKeyType() instanceof MixedType) {
					$parameterType = new ArrayType(new IntegerType(), $parameterType->getItemType());
				}
			}

			$resolved[$parameterName] = new ParamTag(
				$parameterType,
				$tagValue->isVariadic
			);
		}

		return $resolved;
	}

	private function resolveReturnTag(PhpDocNode $phpDocNode, NameScope $nameScope): ?\PHPStan\PhpDoc\Tag\ReturnTag
	{
		foreach ($phpDocNode->getReturnTagValues() as $tagValue) {
			return new ReturnTag($this->typeNodeResolver->resolve($tagValue->type, $nameScope));
		}

		return null;
	}


	/**
	 * @param PhpDocNode $phpDocNode
	 * @param NameScope  $nameScope
	 * @return \PHPStan\PhpDoc\Tag\GenericTag[]
	 */
	private function resolveGenericTag(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		foreach ($phpDocNode->getGenericTagValues() as $tagValue) {
			$resolved[$tagValue->name] = new GenericTag(
				$tagValue->constraint ? $this->typeNodeResolver->resolve($tagValue->constraint, $nameScope) : null,
				$tagValue->constraintType,
				$tagValue->varianceType
			);
		}

		return $resolved;
	}


	/**
	 * @return \PHPStan\PhpDoc\Tag\InheritTag[]
	 */
	private function resolveInheritTag(PhpDocNode $phpDocNode, NameScope $nameScope): array
	{
		$resolved = [];
		foreach ($phpDocNode->getExtendsTagValues() as $tagValue) {
			$types = [];
			foreach ($tagValue->type->genericTypes as $genericType) {
				$types[] = $this->typeNodeResolver->resolve($genericType, $nameScope);
			}
			$resolved[$tagValue->type->type->name] = new InheritTag('extends', $types);
		}

		foreach ($phpDocNode->getExtendsTagValues() as $tagValue) {
			$types = [];
			foreach ($tagValue->type->genericTypes as $genericType) {
				$types[] = $this->typeNodeResolver->resolve($genericType, $nameScope);
			}
			$resolved[$tagValue->type->type->name] = new InheritTag('implements', $types);
		}

		return $resolved;
	}

}
