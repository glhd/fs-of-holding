<?php

namespace Glhd\FsOfHolding;

use Illuminate\Support\ServiceProvider;

class HoldingProvider extends ServiceProvider
{
	public function register(): void
	{
		HoldingStream::registerWrapper();
	}
}
