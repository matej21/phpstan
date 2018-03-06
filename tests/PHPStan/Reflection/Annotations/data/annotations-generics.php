<?php

namespace AnnotationsGenerics;

use OtherNamespace\Test as OtherTest;
use OtherNamespace\Ipsum;

interface Item
{

}

class FooItem implements Item
{

}


/**
 * @generic T extends Item
 */
class Collection
{
	/** @var T[]  */
	public $items = [];


	/**
	 * @return T
	 */
	public function get(): Item
	{
	}


	/**
	 * @param T $item
	 */
	public function add(Item $item)
	{

	}
}


/**
 * @generic T
 */
class CollectionWithoutConstraint
{
	/** @var T[] */
	public $items = [];


	/**
	 * @return T
	 */
	public function get()
	{
	}


	/**
	 * @param T $item
	 */
	public function add($item)
	{

	}
}
