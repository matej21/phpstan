<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface GenericClassReflectionExtension
{
	public function getGenericClassReflection(ClassReflection $classReflection): ?GenericClassReflection;
}

