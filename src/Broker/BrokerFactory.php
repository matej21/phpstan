<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\Reflection\Annotations\AnnotationsGenericClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsMethodsClassReflectionExtension;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;
use PHPStan\Reflection\FunctionReflectionFactory;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\PhpDefect\PhpDefectClassReflectionExtension;
use PHPStan\Reflection\SignatureMap\FunctionDumper;
use PHPStan\Type\FileTypeMapper;

class BrokerFactory
{

	public const PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.propertiesClassReflectionExtension';
	public const METHODS_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.methodsClassReflectionExtension';
	public const GENERIC_TYPES_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.genericTypesClassReflectionExtension';
	public const DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicMethodReturnTypeExtension';
	public const DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicStaticMethodReturnTypeExtension';
	public const DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicFunctionReturnTypeExtension';

	/** @var \Nette\DI\Container */
	private $container;

	public function __construct(\Nette\DI\Container $container)
	{
		$this->container = $container;
	}

	public function create(): Broker
	{
		$tagToService = function (array $tags) {
			return array_map(function (string $serviceName) {
				return $this->container->getService($serviceName);
			}, array_keys($tags));
		};

		$phpClassReflectionExtension = $this->container->getByType(PhpClassReflectionExtension::class);
		$annotationsMethodsClassReflectionExtension = $this->container->getByType(AnnotationsMethodsClassReflectionExtension::class);
		$annotationsPropertiesClassReflectionExtension = $this->container->getByType(AnnotationsPropertiesClassReflectionExtension::class);
		$phpDefectClassReflectionExtension = $this->container->getByType(PhpDefectClassReflectionExtension::class);
		$annotationGenericTypesClassReflectionExtension = $this->container->getByType(AnnotationsGenericClassReflectionExtension::class);

		return new Broker(
			array_merge([$phpClassReflectionExtension, $phpDefectClassReflectionExtension], $tagToService($this->container->findByTag(self::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG)), [$annotationsPropertiesClassReflectionExtension]),
			array_merge([$phpClassReflectionExtension], $tagToService($this->container->findByTag(self::METHODS_CLASS_REFLECTION_EXTENSION_TAG)), [$annotationsMethodsClassReflectionExtension]),
			array_merge($tagToService($this->container->findByTag(self::GENERIC_TYPES_CLASS_REFLECTION_EXTENSION_TAG)), [$annotationGenericTypesClassReflectionExtension]),
			$tagToService($this->container->findByTag(self::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG)),
			$tagToService($this->container->findByTag(self::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG)),
			$tagToService($this->container->findByTag(self::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG)),
			$this->container->getByType(FunctionReflectionFactory::class),
			$this->container->getByType(FileTypeMapper::class),
			$this->container->getByType(FunctionDumper::class)
		);
	}

}
