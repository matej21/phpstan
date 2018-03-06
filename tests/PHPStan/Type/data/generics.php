<?php declare(strict_types = 1);

interface Animal
{

}

interface Cat extends Animal
{

}

interface AnimalSpeaker
{
	public function speak(Animal $animal): string;
}


interface AnimalMaker
{
	public function make(): Animal;
}

interface AnimalMakerAndSpeaker extends AnimalSpeaker, AnimalMaker
{

}
