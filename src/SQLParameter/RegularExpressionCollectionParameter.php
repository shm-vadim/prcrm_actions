<?php

final class RegularExpressionCollectionParameter
{
    /**
     * @var array
     */
    private $collection;

    public function __construct(array $collection)
    {
        $this->collection = $collection;
    }

    public function getCollection(): array
    {
        return $this->collection;
    }
}
