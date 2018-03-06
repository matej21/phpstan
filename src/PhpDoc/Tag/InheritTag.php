<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

class InheritTag
{
	/** @var string extends or implements */
	private $inheritanceType;

	/** @var Type[] */
	private $genericTypes;


	/**
	 * @param string $inheritanceType
	 * @param Type[] $genericTypes
	 */
	public function __construct(string $inheritanceType, array $genericTypes)
	{
		$this->inheritanceType = $inheritanceType;
		$this->genericTypes = $genericTypes;
	}


	public function getInheritanceType(): string
	{
		return $this->inheritanceType;
	}


	/**
	 * @return Type[]
	 */
	public function getGenericTypes(): array
	{
		return $this->genericTypes;
	}


	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['inheritanceType'],
			$properties['genericTypes']
		);
	}

}
