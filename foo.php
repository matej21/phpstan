<?php declare(strict_types = 1);

class Item
{

}

/**
 * @generic X extends S
 * @generic T extends Item
 * @generic S extends T
 */
class Foo
{

}

$x = new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item')));


$tags = [
	'X' => new \PHPStan\PhpDoc\Tag\GenericTag(new \PHPStan\Type\UnresolvedGenericType('S'), '', ''),
	'T' => new \PHPStan\PhpDoc\Tag\GenericTag(new \PHPStan\Type\ObjectType('Item'), '', ''),
	'S' => new \PHPStan\PhpDoc\Tag\GenericTag(new \PHPStan\Type\UnresolvedGenericType('T'), '', ''),
];

$types = [
	'X' => new \PHPStan\Type\UnresolvedGenericType('X', new \PHPStan\Type\UnresolvedGenericType('S')),
	'T' => new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item')),
	'S' => new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T')),
];

$types = [
	'X' => new \PHPStan\Type\UnresolvedGenericType('X', new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T'))),
	'T' => new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item')),
	'S' => new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item'))),
];

$types = [
	'X' => new \PHPStan\Type\UnresolvedGenericType('X', new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item')))),
	'T' => new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item')),
	'S' => new \PHPStan\Type\UnresolvedGenericType('S', new \PHPStan\Type\UnresolvedGenericType('T', new \PHPStan\Type\ObjectType('Item'))),
];
