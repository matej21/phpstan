<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\GenericClassReflection;
use PHPStan\Reflection\GenericTypeReflection;
use PHPStan\Reflection\GenericClassReflectionExtension;
use PHPStan\Reflection\InheritanceReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\MixedType;
use PHPStan\Type\ResolvableGenericType;
use PHPStan\Type\UnresolvedGenericType;

class AnnotationsGenericClassReflectionExtension implements GenericClassReflectionExtension
{

	/** @var FileTypeMapper */
	private $fileTypeMapper;

	/** @var GenericClassReflection[] */
	private $genericReflectionCache = [];


	public function __construct(FileTypeMapper $fileTypeMapper)
	{
		$this->fileTypeMapper = $fileTypeMapper;
	}


	public function getGenericClassReflection(ClassReflection $classReflection): ?GenericClassReflection
	{
		$cacheKey = md5(serialize([$classReflection->getName(), $classReflection->getGenericTypes()]));
		if (!array_key_exists($cacheKey, $this->genericReflectionCache)) {
			$this->genericReflectionCache[$cacheKey] = $this->createGenericTypes($classReflection);
		}

		return $this->genericReflectionCache[$cacheKey];
	}


	public function createGenericTypes(ClassReflection $classReflection): ?GenericClassReflection
	{
		$fileName = $classReflection->getFileName();
		if ($fileName === false) {
			return null;
		}

		$docComment = $classReflection->getNativeReflection()->getDocComment();
		if ($docComment === false) {
			return null;
		}

		// intentionally not passing reflection to prevent recursion. generic types resolving is solved bellow
		$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, $classReflection, $docComment, false);

		$genericTypes = $classReflection->getGenericTypes();
		$i = 0;
		$typesMap = [];
		foreach ($resolvedPhpDoc->getGenericTags() as $name => $tag) {
			$type = $genericTypes[$i++] ?? new UnresolvedGenericType($classReflection->getName(), $name);
			$typesMap[$name] = $type;
		}

		$i = 0;
		foreach ($resolvedPhpDoc->getGenericTags() as $name => $tag) {
			$constraint = $tag->getConstraint();
			if ($constraint instanceof ResolvableGenericType) {
				$constraint = $constraint->resolveGenericType($genericTypes);
			}
			$type = $genericTypes[$i++] ?? new UnresolvedGenericType($classReflection->getName(), $name, $constraint);
			$typesMap[$name] = $type;
		}


		/** @var UnresolvedGenericType[] $typesMap */
		$typesMap = [];
		foreach ($resolvedPhpDoc->getGenericTags() as $name => $tag) {
			$typesMap[$name] = new UnresolvedGenericType($classReflection->getName(), $name, $tag->getConstraint());
		}
		$checksum = md5(serialize($typesMap));
		$i = 0;
		do {
			foreach ($typesMap as $name => $type) {
				$constraint = $type->getConstraint();
				if ($constraint instanceof ResolvableGenericType) {
					$constraint = $constraint->resolveGenericType($typesMap);
				}
				$typesMap[$name] = new UnresolvedGenericType($classReflection->getName(), $name, $constraint);
			}
			if ($i++ > 5) {
				throw new \LogicException('possible recursion');
			}
		} while ($checksum === ($checksum = md5(serialize($typesMap))));


		$resolvedPhpDoc = $resolvedPhpDoc->resolveGenericTypes($typesMap);

		$genericTypesReflection = [];
		foreach ($resolvedPhpDoc->getGenericTags() as $name => $tag) {
			$varianceType = $tag->getVarianceType();
			$genericTypesReflection[] = new GenericTypeReflection(
				$name,
				$tag->getConstraint(), //todo asi posilat cely typ
				null, //todo
				$varianceType !== 'out',
				$varianceType !== 'in'
			);
		}

		$inheritanceReflections = [];
		foreach ($resolvedPhpDoc->getInheritTags() as $class => $tag) {
			$inheritanceReflections[] = new InheritanceReflection($tag->getInheritanceType(), $class, $tag->getGenericTypes());
		}

		return new GenericClassReflection($genericTypesReflection, $inheritanceReflections);
	}

}
