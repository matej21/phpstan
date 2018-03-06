<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\GenericClassReflection;
use PHPStan\Reflection\GenericTypeReflection;
use PHPStan\Reflection\GenericClassReflectionExtension;
use PHPStan\TrinaryLogic;


class GenericTypeTest extends \PHPStan\Testing\TestCase
{
	public function dataIsSuperTypeOfAndAccepts(): array
	{
		return [
			[
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Cat::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new MixedType()]),
				TrinaryLogic::createMaybe(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Cat::class)]),
				new GenericObjectType(new ObjectType(\AnimalMaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createMaybe(),
				false,
			],


			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Cat::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Cat::class)]),
				TrinaryLogic::createMaybe(),
				false,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new UnionType([new ObjectType(\Animal::class), new ObjectType(\DateTime::class)])]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalSpeaker::class), [new MixedType()]),
				TrinaryLogic::createYes(),
				true,
			],


			[
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Cat::class)]),
				TrinaryLogic::createMaybe(),
				false,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createYes(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Animal::class)]),
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new MixedType()]),
				TrinaryLogic::createMaybe(),
				true,
			],
			[
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Cat::class)]),
				new GenericObjectType(new ObjectType(\AnimalMakerAndSpeaker::class), [new ObjectType(\Animal::class)]),
				TrinaryLogic::createMaybe(),
				false,
			],
		];
	}


	/**
	 * @dataProvider dataIsSuperTypeOfAndAccepts
	 * @param GenericObjectType $type
	 * @param Type              $otherType
	 * @param TrinaryLogic      $expectedResult
	 */
	public function testIsSuperTypeOf(GenericObjectType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$extension = $this->createStaticGenericExtension();
		$this->createBroker([], [], [$extension]);

		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(), $otherType->describe())
		);
	}


	/**
	 * @dataProvider dataIsSuperTypeOfAndAccepts
	 * @param GenericObjectType $type
	 * @param Type              $otherType
	 * @param bool              $expectedResult
	 */
	public function testAccepts(GenericObjectType $type, Type $otherType, TrinaryLogic $_, bool $expectedResult): void
	{
		$extension = $this->createStaticGenericExtension();
		$this->createBroker([], [], [$extension]);

		$actualResult = $type->accepts($otherType);
		$this->assertSame(
			$expectedResult,
			$actualResult,
			sprintf('%s -> accepts(%s)', $type->describe(), $otherType->describe())
		);
	}


	protected function createStaticGenericExtension(): GenericClassReflectionExtension
	{
		return new class implements GenericClassReflectionExtension
		{
			public function getGenericClassReflection(ClassReflection $classReflection): ?GenericClassReflection
			{
				$types = [];
				if ($classReflection->getName() === \AnimalSpeaker::class) {
					$types = [
						new GenericTypeReflection('T', null, null, true, false),
					];
				} elseif ($classReflection->getName() === \AnimalMaker::class) {
					$types = [
						new GenericTypeReflection('T', null, null, false, true),
					];
				} elseif ($classReflection->getName() === \AnimalMakerAndSpeaker::class) {
					$types = [
						new GenericTypeReflection('T', null, null, true, true),
					];
				}
				return new GenericClassReflection($types, []);
			}
		};
	}

}
