<?php

declare(strict_types=1);

namespace Omega\Application;

use Omega\Config\ConfigRepository;
use Omega\Config\SettingsRepository;

interface ApplicationInterface
{
	/**
	 * Get the unique application identifier.
	 *
	 * @return string Return the application identifier.
	 */
	public function getId(): string;

	/**
	 * Get the application id in snake_case format.
	 *
	 * @return string|array Return the application in snake_case format.
	 */
	public function getIdAsUnderscore(): array|string;

	/**
	 * Get the base path of the application.
	 *
	 * @return string Return the absolute base path.
	 */
	public function getBasePath(): string;

	/**
	 * Get the root directory of the plugin.
	 *
	 * @return string Return the plugin root dir.
	 */
	public function getPluginRoot(): string;

	/**
	 * Get the main plugin file path.
	 *
	 * @return string Return the plugin file.
	 */
	public function getPluginFile(): string;

	/**
	 * Register a service provider within the application container.
	 *
	 * @param object|string $provider Service provider class name or instance
	 * @return object|string Registered service provider instance
	 */
	public function register(object|string $provider): object|string;

	/**
	 * Bootstrap all registered service providers.
	 *
	 * Calls the "boot" method on each provider if available.
	 *
	 * @return void
	 */
	public function bootstrap(): void;

	/**
	 * Add a route file to the application.
	 *
	 * @param string $path Path to the route file
	 * @param string $type Route type (e.g. "api" or "admin")
	 * @return void
	 */
	public function addRouteFile(string $path, string $type = 'api'): void;

	/**
	 * Register a migration folder path.
	 *
	 * @param string $path Directory containing migration files
	 * @return void
	 */
	public function addMigrationFolder(string $path): void;

	/**
	 * Get all registered API route file paths.
	 *
	 * @return array List of API route file paths
	 */
	public function getRestRouteFiles(): array;

	/**
	 * Get all registered admin route file paths.
	 *
	 * @return array List of admin route file paths
	 */
	public function getAdminRouteFiles(): array;

	/**
	 * Get all registered migration folder paths.
	 *
	 * @return array List of migration directories
	 */
	public function getMigrationFolders(): array;

	/**
	 * Retrieve the plugin version from its main file header.
	 *
	 * @return string Plugin version or default if not found
	 */
	public function getVersion(): string;

	/**
	 * Get the configuration repository instance.
	 *
	 * @return ConfigRepository Configuration service instance
	 */
	public function config(): ConfigRepository;

	/**
	 * Get the settings repository instance.
	 *
	 * @return SettingsRepository Settings service instance
	 */
	public function settings(): SettingsRepository;
}
