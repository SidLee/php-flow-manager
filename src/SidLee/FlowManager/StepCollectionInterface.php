<?php
namespace SidLee\FlowManager;

interface StepCollectionInterface extends StepCollectionAddableInterface, \Iterator, \ArrayAccess, \Countable {
    /**
     * @param StepCollectionAddableInterface[] $items
     */
    public function __construct(array $items = array());

    /**
     * @param string $key
     * @param StepCollectionAddableInterface $item
     */
    public function add($key, StepCollectionAddableInterface $item);
} 