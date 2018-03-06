<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class NameScope
{

	/**
	 * @var string|null
	 */
	private $namespace;

	/**
	 * @var string[] alias(string) => fullName(string)
	 */
	private $uses;

	/**
	 * @var string|null
	 */
	private $className;

	/**
	 * @var array|null
	 */
	private $genericTypeNames;


	public function __construct(string $namespace = null, array $uses = [], string $className = null, array $genericTypeNames = null)
	{
		$this->namespace = $namespace;
		$this->uses = $uses;
		$this->className = $className;
		$this->genericTypeNames = $genericTypeNames;
	}

	public function getClassName(): ?string
	{
		return $this->className;
	}


	public function hasGenericTypeNames(): bool
	{
		return $this->genericTypeNames !== null;
	}


	public function withGenericTypeNames(array $genericTypeNames): self
	{
		return new self($this->namespace, $this->uses, $this->className, $genericTypeNames);
	}


	public function isGenericType(string $name): bool
	{
		return in_array($name, $this->genericTypeNames ?? [], true);
	}


	public function resolveStringName(string $name): string
	{
		if (strpos($name, '\\') === 0) {
			return ltrim($name, '\\');
		}

		$nameParts = explode('\\', $name);
		$firstNamePart = $nameParts[0];
		if (isset($this->uses[$firstNamePart])) {
			if (count($nameParts) === 1) {
				return $this->uses[$firstNamePart];
			}
			array_shift($nameParts);
			return sprintf('%s\\%s', $this->uses[$firstNamePart], implode('\\', $nameParts));
		}

		if ($this->namespace !== null) {
			return sprintf('%s\\%s', $this->namespace, $name);
		}

		return $name;
	}

}
