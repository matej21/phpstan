<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;


class InheritanceReflection
{
	/** @var string implements or extends */
	private $type;

	/** @var string */
	private $name;

	/** @var Type[] */
	private $genericTypes;


	/**
	 * @param string $type
	 * @param string $name
	 * @param Type[] $genericTypes
	 */
	public function __construct(string $type, string $name, array $genericTypes)
	{
		$this->type = $type;
		$this->name = $name;
		$this->genericTypes = $genericTypes;
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * @return Type[]
	 */
	public function getGenericTypes(): array
	{
		return $this->genericTypes;
	}
}
