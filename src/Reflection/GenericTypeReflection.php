<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

class GenericTypeReflection
{
	/** @var string */
	private $name;

	/** @var null|Type */
	private $constraint;

	/** @var null|Type */
	private $defaultType;

	/** @var bool */
	private $covariant;

	/** @var bool */
	private $contravariant;


	public function __construct(string $name, ?Type $constraint = null, ?Type $defaultType = null, bool $in = true, bool $out = true)
	{
		assert($out || $in);
		$this->name = $name;
		$this->constraint = $constraint;
		$this->defaultType = $defaultType;
		$this->covariant = $out && !$in;
		$this->contravariant = $in && !$out;
	}


	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}


	public function hasDefinedConstraint(): bool
	{
		return $this->constraint !== null;
	}


	public function getConstraint(): ?Type
	{
		return $this->constraint;
	}


	public function isOptional(): bool
	{
		return $this->defaultType !== null;
	}


	public function getDefaultType(): ?Type
	{
		return $this->defaultType;
	}


	public function isCovariant(): bool
	{
		return $this->covariant;
	}


	public function isContravariant(): bool
	{
		return $this->contravariant;
	}


	public function isInvariant(): bool
	{
		return !$this->covariant && !$this->contravariant;
	}
}
