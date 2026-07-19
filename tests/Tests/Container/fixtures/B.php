<?php

declare(strict_types=1);

namespace Tests\Container\Fixtures;

class B
{
	public A $a;

	public function __construct(A $a)
	{
		$this->a = $a;
	}
}
