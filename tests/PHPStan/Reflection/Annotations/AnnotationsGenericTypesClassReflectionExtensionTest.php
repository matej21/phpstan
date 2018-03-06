<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use AnnotationsGenerics\Collection;
use AnnotationsGenerics\CollectionWithoutConstraint;
use AnnotationsGenerics\FooItem;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Type\ObjectType;

class AnnotationsGenericTypesClassReflectionExtensionTest extends \PHPStan\Testing\TestCase
{

	public function testGenerics(): void
	{
		/** @var Broker $broker */
		$broker = $this->getContainer()->getByType(Broker::class);
		$class = $broker->getClass(Collection::class, [
			new ObjectType(FooItem::class)
		]);

		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canCallMethod')->willReturn(true);

		$method = $class->getMethod('get', $scope);
		$this->assertSame('AnnotationsGenerics\\FooItem', $method->getReturnType()->describe());

		$method = $class->getMethod('add', $scope);
		$this->assertSame('AnnotationsGenerics\\FooItem', $method->getParameters()[0]->getType()->describe());

		$property = $class->getProperty('items', $scope);

		$this->assertSame('array<AnnotationsGenerics\\FooItem>', $property->getType()->describe());
	}


	public function testGenericsUnresolved(): void
	{
		/** @var Broker $broker */
		$broker = $this->getContainer()->getByType(Broker::class);
		$class = $broker->getClass(CollectionWithoutConstraint::class);

		$scope = $this->createMock(Scope::class);
		$scope->method('isInClass')->willReturn(true);
		$scope->method('getClassReflection')->willReturn($class);
		$scope->method('canCallMethod')->willReturn(true);
//
//		$method = $class->getMethod('get', $scope);
//		$this->assertSame('<T>', $method->getReturnType()->describe());
//
//		$method = $class->getMethod('add', $scope);
//		$this->assertSame('<T>', $method->getParameters()[0]->getType()->describe());

		$property = $class->getProperty('items', $scope);

		$this->assertSame('array<<T>>', $property->getType()->describe());
	}
}
