<?php

declare(strict_types=1);

namespace Omega\Routing;

use Omega\Application\ApplicationInterface;

use function file_exists;

readonly class RouteLoader
{
	public function __construct(private ApplicationInterface $app)
	{
	}

	public function loadRestRoutes(): void
	{
		$this->load([
			$this->app->getBasePath() . '/routes/api.php',
			...$this->app->getRestRouteFiles()
		]);
	}

	public function loadAdminRoutes(): void
	{
		$this->load([
			$this->app->getBasePath() . '/routes/admin.php',
			...$this->app->getAdminRouteFiles()
		]);
	}

	private function load(array $files): void
	{
		foreach ($files as $file) {
			if (file_exists($file)) {
				require_once $file;
			}
		}
	}
}
