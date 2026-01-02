<?php

namespace App\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;

/**
 * Custom Pagerfanta adapter for pre-paginated results with known total count.
 * Used when pagination is done at the database level with a known total count.
 */
class ArrayAdapterWithCount implements AdapterInterface
{
    private array $array;
    private int $nbResults;

    public function __construct(array $array, int $nbResults)
    {
        $this->array = $array;
        $this->nbResults = $nbResults;
    }

    public function getNbResults(): int
    {
        return $this->nbResults;
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->array;
    }
}