<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;

class GenericTag
{
	/** @var null|\PHPStan\Type\Type */
	private $constraint;

	/** @var string 'extends', 'implements' or '' */
	public $constraintType;

	/** @var string 'in', 'out' or '' */
	public $varianceType;


	public function __construct(?Type $constraint, string $constraintType, string $varianceType)
	{
		$this->constraint = $constraint;
		$this->constraintType = $constraintType;
		$this->varianceType = $varianceType;
	}


	public function getConstraint(): ?Type
	{
		return $this->constraint;
	}


	public function getConstraintType(): string
	{
		return $this->constraintType;
	}


	public function getVarianceType(): string
	{
		return $this->varianceType;
	}


	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['constraint'],
			$properties['constraintType'],
			$properties['varianceType']
		);
	}

}
