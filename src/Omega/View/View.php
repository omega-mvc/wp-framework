<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\View;

use Omega\Application\Application;
use Omega\View\Exception\ViewFileNotFoundException;

use function extract;
use function file_exists;
use function str_replace;

/**
 * Render application view files.
 *
 * This class provides a minimal view rendering layer for the framework.
 * It resolves view names expressed in dot notation into physical file paths
 * inside the application's view directory and injects data into those files
 * before including them.
 *
 * Example:
 *
 * "users.profile" becomes:
 * resources/views/users/profile.php
 *
 * The renderer depends on the current application instance in order
 * to determine the framework base path and locate the correct
 * resources' directory.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class View
{
    /**
     * Create a new view renderer instance.
     *
     * @param Application $app The current application container instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Render a view file.
     *
     * The provided view name may use dot notation to represent
     * nested directories. The supplied data array is extracted
     * into individual variables so they become directly available
     * inside the included template.
     *
     * Example:
     *
     * make('admin.dashboard', ['title' => 'Dashboard']);
     *
     * makes the variable $title available inside:
     *
     * resources/views/admin/dashboard.php
     *
     * @param string $view The logical name of the view using dot notation.
     * @param array $data The data to expose to the view file.
     * @return void
     * @throws ViewFileNotFoundException Thrown when the resolved view file does not exist.
     */
    public function make(string $view, array $data = []): void
    {
        $viewPath = $this->getViewPath($view);

        if (file_exists($viewPath)) {
            extract($data);
            include $viewPath;
        } else {
            throw new ViewFileNotFoundException($view);
        }
    }

    /**
     * Resolve the absolute path of a view file.
     *
     * Dot notation segments are converted into directory separators
     * so the framework can locate nested view files inside the
     * resources/views directory.
     *
     * Example:
     *
     * "blog.post" becomes:
     * /resources/views/blog/post.php
     *
     * @param string $view The logical view name.
     * @return string The absolute path to the resolved view file.
     */
    protected function getViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);

        return $this->app->getBasePath() . "/resources/views/$view.php";
    }
}
