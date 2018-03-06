<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\ParamTag;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\SignatureMap\FunctionDumper;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use ReflectionClass;

class Broker
{

	/** @var \PHPStan\Reflection\PropertiesClassReflectionExtension[] */
	private $propertiesClassReflectionExtensions;

	/** @var \PHPStan\Reflection\MethodsClassReflectionExtension[] */
	private $methodsClassReflectionExtensions;

	/** @var \PHPStan\Reflection\GenericClassReflectionExtension[] */
	private $genericTypeReflectionExtensions;

	/** @var \PHPStan\Type\DynamicMethodReturnTypeExtension[] */
	private $dynamicMethodReturnTypeExtensions = [];

	/** @var \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] */
	private $dynamicStaticMethodReturnTypeExtensions = [];

	/** @var \PHPStan\Type\DynamicMethodReturnTypeExtension[] */
	private $dynamicMethodReturnTypeExtensionsByClass;

	/** @var \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] */
	private $dynamicStaticMethodReturnTypeExtensionsByClass;

	/** @var \PHPStan\Type\DynamicFunctionReturnTypeExtension[] */
	private $dynamicFunctionReturnTypeExtensions = [];

	/** @var \PHPStan\Reflection\ClassReflection[] */
	private $classReflections = [];

	/** @var \PHPStan\Reflection\FunctionReflectionFactory */
	private $functionReflectionFactory;

	/** @var \PHPStan\Type\FileTypeMapper */
	private $fileTypeMapper;

	/** @var \PHPStan\Reflection\SignatureMap\FunctionDumper */
	private $functionDumper;

	/** @var \PHPStan\Reflection\FunctionReflection[] */
	private $functionReflections = [];

	/** @var \PHPStan\Reflection\Php\PhpFunctionReflection[] */
	private $customFunctionReflections = [];

	/** @var null|self */
	private static $instance;

	/** @var bool[] */
	private $hasClassCache;

	/** @var \PHPStan\Reflection\Native\NativeFunctionReflection[] */
	private static $functionMap = [];

	/**
	 * @param \PHPStan\Reflection\PropertiesClassReflectionExtension[] $propertiesClassReflectionExtensions
	 * @param \PHPStan\Reflection\MethodsClassReflectionExtension[]    $methodsClassReflectionExtensions
	 * @param \PHPStan\Reflection\GenericClassReflectionExtension[]    $genericTypeReflectionExtensions
	 * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[]         $dynamicMethodReturnTypeExtensions
	 * @param \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]   $dynamicStaticMethodReturnTypeExtensions
	 * @param \PHPStan\Type\DynamicFunctionReturnTypeExtension[]       $dynamicFunctionReturnTypeExtensions
	 * @param \PHPStan\Reflection\FunctionReflectionFactory            $functionReflectionFactory
	 * @param \PHPStan\Type\FileTypeMapper                             $fileTypeMapper
	 * @param \PHPStan\Reflection\SignatureMap\FunctionDumper          $functionDumper
	 */
	public function __construct(
		array $propertiesClassReflectionExtensions,
		array $methodsClassReflectionExtensions,
		array $genericTypeReflectionExtensions,
		array $dynamicMethodReturnTypeExtensions,
		array $dynamicStaticMethodReturnTypeExtensions,
		array $dynamicFunctionReturnTypeExtensions,
		FunctionReflectionFactory $functionReflectionFactory,
		FileTypeMapper $fileTypeMapper,
		FunctionDumper $functionDumper
	)
	{
		$this->propertiesClassReflectionExtensions = $propertiesClassReflectionExtensions;
		$this->methodsClassReflectionExtensions = $methodsClassReflectionExtensions;
		$this->genericTypeReflectionExtensions = $genericTypeReflectionExtensions;
		foreach (array_merge($propertiesClassReflectionExtensions, $methodsClassReflectionExtensions, $dynamicMethodReturnTypeExtensions, $dynamicStaticMethodReturnTypeExtensions, $dynamicFunctionReturnTypeExtensions) as $extension) {
			if ($extension instanceof BrokerAwareExtension) {
				$extension->setBroker($this);
			}
		}

		$this->dynamicMethodReturnTypeExtensions = $dynamicMethodReturnTypeExtensions;
		$this->dynamicStaticMethodReturnTypeExtensions = $dynamicStaticMethodReturnTypeExtensions;

		foreach ($dynamicFunctionReturnTypeExtensions as $functionReturnTypeExtension) {
			$this->dynamicFunctionReturnTypeExtensions[] = $functionReturnTypeExtension;
		}

		$this->functionReflectionFactory = $functionReflectionFactory;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->functionDumper = $functionDumper;

		self::$instance = $this;
	}

	public static function getInstance(): self
	{
		if (self::$instance === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		return self::$instance;
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\DynamicMethodReturnTypeExtension[]
	 */
	public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
		}
		return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @param string $className
	 * @return \PHPStan\Type\DynamicStaticMethodReturnTypeExtension[]
	 */
	public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicStaticMethodReturnTypeExtensions as $extension) {
				$byClass[$extension->getClass()][] = $extension;
			}

			$this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
		}
		return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		return $this->dynamicFunctionReturnTypeExtensions;
	}

	/**
	 * @param \PHPStan\Type\DynamicMethodReturnTypeExtension[]|\PHPStan\Type\DynamicStaticMethodReturnTypeExtension[] $extensions
	 * @param string $className
	 * @return mixed[]
	 */
	private function getDynamicExtensionsForType(array $extensions, string $className): array
	{
		$extensionsForClass = [];
		$class = $this->getClass($className);
		foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass = array_merge($extensionsForClass, $extensions[$extensionClassName]);
		}

		return $extensionsForClass;
	}


	/**
	 * @param Type[] $genericTypes
	 */
	public function getClass(string $className, array $genericTypes = []): \PHPStan\Reflection\ClassReflection
	{
		if (!$this->hasClass($className)) {
			throw new \PHPStan\Broker\ClassNotFoundException($className);
		}

		$key = serialize([$className, $genericTypes]);

		if (!isset($this->classReflections[$key])) {
			$reflectionClass = new ReflectionClass($className);
			$classReflection = $this->getClassFromReflection(
				$reflectionClass,
				$reflectionClass->getName(),
				$reflectionClass->isAnonymous(),
				$genericTypes
			);
			$this->classReflections[$key] = $classReflection;
			if ($className !== $reflectionClass->getName()) {
				// class alias optimization
				$key = serialize([$className, $genericTypes]);
				$this->classReflections[$key] = $classReflection;
			}
		}

		return $this->classReflections[$key];
	}


	/**
	 * @param Type[] $genericTypes
	 */
	public function getClassFromReflection(\ReflectionClass $reflectionClass, string $displayName, bool $anonymous, array $genericTypes): \PHPStan\Reflection\ClassReflection
	{
		$key = serialize([$reflectionClass->getName(), $genericTypes]);

		if (!isset($this->classReflections[$key])) {
			$classReflection = new ClassReflection(
				$this,
				$this->propertiesClassReflectionExtensions,
				$this->methodsClassReflectionExtensions,
				$this->genericTypeReflectionExtensions,
				$displayName,
				$reflectionClass,
				$anonymous,
				$genericTypes
			);
			$this->classReflections[$key] = $classReflection;
		}

		return $this->classReflections[$key];
	}

	public function hasClass(string $className): bool
	{
		if (isset($this->hasClassCache[$className])) {
			return $this->hasClassCache[$className];
		}

		spl_autoload_register($autoloader = function (string $autoloadedClassName) use ($className): void {
			if ($autoloadedClassName !== $className && !$this->isExistsCheckCall()) {
				throw new \PHPStan\Broker\ClassAutoloadingException($autoloadedClassName);
			}
		});

		try {
			return $this->hasClassCache[$className] = class_exists($className) || interface_exists($className) || trait_exists($className);
		} catch (\PHPStan\Broker\ClassAutoloadingException $e) {
			throw $e;
		} catch (\Throwable $t) {
			throw new \PHPStan\Broker\ClassAutoloadingException(
				$className,
				$t
			);
		} finally {
			spl_autoload_unregister($autoloader);
		}
	}

	public function getFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): \PHPStan\Reflection\FunctionReflection
	{
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if ($functionName === null) {
			throw new \PHPStan\Broker\FunctionNotFoundException((string) $nameNode);
		}

		$lowerCasedFunctionName = strtolower($functionName);
		if (!isset($this->functionReflections[$lowerCasedFunctionName])) {
			$nativeFunctionFilename = $this->functionDumper->getFilename($functionName);
			if (isset(self::$functionMap[$nativeFunctionFilename])) {
				return $this->functionReflections[$lowerCasedFunctionName] = self::$functionMap[$nativeFunctionFilename];
			}
			if (file_exists($nativeFunctionFilename)) {
				$functionReflection = require_once $nativeFunctionFilename;
				if (!$functionReflection instanceof \PHPStan\Reflection\Native\NativeFunctionReflection) {
					throw new \PHPStan\ShouldNotHappenException(sprintf('File %s could not be loaded', $nativeFunctionFilename));
				}
				self::$functionMap[$nativeFunctionFilename] = $functionReflection;
				$this->functionReflections[$lowerCasedFunctionName] = $functionReflection;
			} else {
				$this->functionReflections[$lowerCasedFunctionName] = $this->getCustomFunction($nameNode, $scope);
			}
		}

		return $this->functionReflections[$lowerCasedFunctionName];
	}

	public function hasFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveFunctionName($nameNode, $scope) !== null;
	}

	public function hasCustomFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		if ($functionName === null) {
			return false;
		}

		$nativeFunctionFilename = $this->functionDumper->getFilename($functionName);
		return !file_exists($nativeFunctionFilename);
	}

	public function getCustomFunction(\PhpParser\Node\Name $nameNode, ?Scope $scope): \PHPStan\Reflection\Php\PhpFunctionReflection
	{
		if (!$this->hasCustomFunction($nameNode, $scope)) {
			throw new \PHPStan\Broker\FunctionNotFoundException((string) $nameNode);
		}

		/** @var string $functionName */
		$functionName = $this->resolveFunctionName($nameNode, $scope);
		$lowerCasedFunctionName = strtolower($functionName);
		if (isset($this->customFunctionReflections[$lowerCasedFunctionName])) {
			return $this->customFunctionReflections[$lowerCasedFunctionName];
		}

		$reflectionFunction = new \ReflectionFunction($functionName);
		$phpDocParameterTags = [];
		$phpDocReturnTag = null;
		if ($reflectionFunction->getFileName() !== false && $reflectionFunction->getDocComment() !== false) {
			$fileName = $reflectionFunction->getFileName();
			$docComment = $reflectionFunction->getDocComment();
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($fileName, null, $docComment);
			$phpDocParameterTags = $resolvedPhpDoc->getParamTags();
			$phpDocReturnTag = $resolvedPhpDoc->getReturnTag();
		}

		$functionReflection = $this->functionReflectionFactory->create(
			$reflectionFunction,
			array_map(function (ParamTag $paramTag): Type {
				return $paramTag->getType();
			}, $phpDocParameterTags),
			$phpDocReturnTag !== null ? $phpDocReturnTag->getType() : null
		);
		$this->customFunctionReflections[$lowerCasedFunctionName] = $functionReflection;

		return $functionReflection;
	}

	public function resolveFunctionName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			return function_exists($name);
		}, $scope);
	}

	public function hasConstant(\PhpParser\Node\Name $nameNode, ?Scope $scope): bool
	{
		return $this->resolveConstantName($nameNode, $scope) !== null;
	}

	public function resolveConstantName(\PhpParser\Node\Name $nameNode, ?Scope $scope): ?string
	{
		return $this->resolveName($nameNode, function (string $name): bool {
			return defined($name);
		}, $scope);
	}

	private function resolveName(
		\PhpParser\Node\Name $nameNode,
		\Closure $existsCallback,
		?Scope $scope
	): ?string
	{
		$name = (string) $nameNode;
		if ($scope !== null && $scope->getNamespace() !== null && !$nameNode->isFullyQualified()) {
			$namespacedName = sprintf('%s\\%s', $scope->getNamespace(), $name);
			if ($existsCallback($namespacedName)) {
				return $namespacedName;
			}
		}

		if ($existsCallback($name)) {
			return $name;
		}

		return null;
	}

	private function isExistsCheckCall(): bool
	{
		$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$existsCallTypes = [
			'class_exists' => true,
			'interface_exists' => true,
			'trait_exists' => true,
		];

		foreach ($debugBacktrace as $traceStep) {
			if (
				isset($traceStep['function'])
				&& isset($existsCallTypes[$traceStep['function']])
				// We must ignore the self::hasClass calls
				&& (!isset($traceStep['file']) || $traceStep['file'] !== __FILE__)
			) {
				return true;
			}
		}

		return false;
	}

}
