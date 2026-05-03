<?php

declare(strict_types=1);

namespace Omega\Paginator;

use Omega\Collection\Collection;

use function array_merge;
use function ceil;
use function filter_var;
use function max;

use const FILTER_VALIDATE_INT;

class Paginator
{
    /** @var int The total number of items before slicing. */
    protected int $total;

    /** @var int The last available page. */
    protected int $lastPage;

    /** @var int The number of items per page. */
    protected int $perPage;

    /** @var int The current page.*/
    protected int $currentPage;

    /** The items for the current page. */
    protected Collection $items;

    protected array $options = [];

    /**
     * Paginator constructor
     *
     * @param Collection $items
     * @param int        $total
     * @param int        $perPage
     * @param int|null   $currentPage
     * @param array      $options
     * @return void
     */
    public function __construct(
        Collection $items,
        int $total,
        int $perPage,
        ?int $currentPage = null,
        array $options = []
    ) {
        $this->options = $options;

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->total = $total;
        $this->perPage = $perPage;
        $this->lastPage = max((int)ceil($total / $perPage), 1);
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->items = $items instanceof Collection ? $items : new Collection($items);
    }

    /**
     * Get the current page for the request.
     *
     * @param int $currentPage
     * @return int
     */
    protected function setCurrentPage(int $currentPage): int
    {
        return $this->isValidPageNumber($currentPage) ? $currentPage : 1;
    }

    /**
     * Determine if the given value is a valid page number.
     *
     * @param int $page
     * @return bool
     */
    protected function isValidPageNumber(int $page): bool
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    public function getCollection(): Collection
    {
        return $this->items;
    }

    public function getAttributes(): array
    {
        return [
            'total'        => $this->total,
            'per_page'     => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page'    => $this->lastPage,
        ];
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getAttributes(),
            [
                'data' => $this->items->toArray(),
            ]
        );
    }
}
