<?php
namespace SidLee\FlowManager;

use Closure;
use SidLee\FlowManager\Exception\FlowException;
use SidLee\FlowManager\Exception\NotImplementedException;
use Traversable;

class FlattenedFlow implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /** @var StepInterface[] */
    private $steps = array();
    /**
     * @var Closure
     */
    private $ascertainStepEligibilityCallback;

    public function __construct(AbstractFlowManager $flowManager)
    {
        if (is_null($flowManager)) {
            throw new \InvalidArgumentException('The parameter $flowManager must not be null.');
        }

        $this->ascertainStepEligibilityCallback = function ($targetStepFQN, NavigationResponse $navResponse, $data) use ($flowManager) {
            return $flowManager->isStepEligibleForNavigation($targetStepFQN, $navResponse, $data);
        };

        $this->compileLevel($flowManager->getKey(), $flowManager->getItems());
    }

    /**
     * @param string $prefix
     * @param        $items StepCollectionInterface
     *
     * @throws NotImplementedException
     */
    private function compileLevel($prefix, StepCollectionInterface $items)
    {
        foreach ($items as $key => $item) {
            if ($item instanceof StepInterface) {
                $this->steps["$prefix.$key"] = $item;
            } else {
                if ($item instanceof StepCollectionInterface) {
                    $this->compileLevel("$prefix.$key", $item);
                } else {
                    throw new NotImplementedException('Unrecognized item type : ' . get_class($item));
                }
            }
        }
    }

    /**
     * @param string $currentStepName
     * @param string $targetStepName
     *
     * @throws Exception\FlowException
     * @return NavigationDirection
     */
    public function detectDirectNavigationDirection($currentStepName, $targetStepName)
    {
        $stepNames = array_keys($this->steps);
        $currentStepIndex = array_search($currentStepName, $stepNames);
        $targetStepNameIndex = array_search($targetStepName, $stepNames);

        if ($targetStepNameIndex === false) {
            throw new FlowException("The step $targetStepName doesn't exist in the current flow.");
        }

        if ($targetStepNameIndex < $currentStepIndex) {
            return NavigationDirection::BACK();
        }

        if ($targetStepNameIndex > $currentStepIndex) {
            return NavigationDirection::NEXT();
        }

        return NavigationDirection::DIRECT();
    }

    /**
     * @param NavigationResponse $navResponse
     * @param string             $currentStepName
     * @param mixed              $data
     *
     * @throws Exception\FlowException
     * @return string
     */
    public function resolvePreviousStepName(NavigationResponse $navResponse, $currentStepName, $data)
    {
        $stepNames = array_keys($this->steps);
        $currentStepIndex = array_search($currentStepName, $stepNames);

        if ($currentStepIndex > 0) {
            for ($previousIndex = $currentStepIndex - 1; $previousIndex >= 0; $previousIndex--) {
                $previousStepName = $stepNames[$previousIndex];
                if ($this->isStepEligible($previousStepName, $navResponse, $data)) {
                    return $previousStepName;
                }
            }
        }

        throw new FlowException('No previous step is eligible.');
    }

    /**
     * @param NavigationResponse $navResponse
     * @param string             $currentStepName
     * @param                    $data
     *
     * @throws Exception\FlowException
     * @return string
     */
    public function resolveNextStepName(NavigationResponse $navResponse, $currentStepName, $data)
    {
        $stepNames = array_keys($this->steps);
        $currentStepIndex = array_search($currentStepName, $stepNames);

        $stepsCount = count($stepNames);

        if ($currentStepIndex + 1 < $stepsCount) {
            for ($nextIndex = $currentStepIndex + 1; $nextIndex < $stepsCount; $nextIndex++) {
                $nextStepName = $stepNames[$nextIndex];
                if ($this->isStepEligible($nextStepName, $navResponse, $data)) {
                    return $nextStepName;
                }
            }
        }

        throw new FlowException('No next step is eligible.');
    }

    /**
     * @param string $stepName
     *
     * @throws FlowException
     * @throws \InvalidArgumentException
     * @return StepInterface
     */
    public function getStep($stepName)
    {
        if (empty($stepName)) {
            throw new \InvalidArgumentException('The parameter $stepKey must have a value.');
        }

        if (array_key_exists($stepName, $this->steps)) {
            return $this->steps[$stepName];
        }

        throw new FlowException("The step key '$stepName' doesn't exist in the current flow.");
    }

    /**
     * @param NavigationResponse $navResponse
     * @param                    $data
     *
     * @return string
     * @throws Exception\FlowException
     */
    public function resolveFirstEligibleStepName(NavigationResponse $navResponse, $data)
    {
        foreach ($this->steps as $stepKey => $step) {
            if ($this->isStepEligible($stepKey, $navResponse, $data)) {
                return $stepKey;
            }
        }

        throw new FlowException('There is no current step and no steps are eligible.');
    }

    /**
     * @param                    $startFromStepName
     * @param NavigationResponse $navResponse
     * @param                    $data
     * @param bool               $excludeDestination
     * @param bool               $excludeNonEligible
     *
     * @throws FlowException
     * @return StepInterface[]
     */
    public function getRange($startFromStepName, NavigationResponse $navResponse, $data, $excludeDestination = true, $excludeNonEligible = true)
    {
        $destinationStepName = $navResponse->getTargetStepName();
        $stepNames = array_keys($this->steps);
        $startStepIndex = array_search($startFromStepName, $stepNames);
        $destinationStepIndex = array_search($destinationStepName, $stepNames);

        $indexRange = range($startStepIndex, $destinationStepIndex);

        if ($excludeDestination) {
            array_pop($indexRange);
        }

        /** @var StepInterface[] $rangeSteps */
        $rangeSteps = array();

        foreach ($indexRange as $index) {
            $rangeStepName = $stepNames[$index];
            $rangeStep = $this->getStep($rangeStepName);

            if (!$excludeNonEligible || $this->isStepEligible($rangeStepName, $navResponse, $data)) {
                $rangeSteps[$rangeStepName] = $rangeStep;
            }
        }

        return $rangeSteps;
    }

    protected function isStepEligible($stepFQN, NavigationResponse $navResponse, $data)
    {
        $closure = $this->ascertainStepEligibilityCallback;
        return $closure($stepFQN, $navResponse, $data);
    }

    public function keys()
    {
        return array_keys($this->steps);
    }

    //region Implementation of \IteratorAggregate, \ArrayAccess, \Countable
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *       <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->steps);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->steps[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->steps[$offset];
        }

        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->steps[$offset] = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->steps[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *       </p>
     *       <p>
     *       The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->steps);
    }
    //endregion
}