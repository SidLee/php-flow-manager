<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\StepCollection;

class StepCollectionTest extends FlowTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoDotAllowedInStepCollectionItemKey()
    {
        /** @var StepCollection $steps */
        $steps = new StepCollection();
        /** @noinspection PhpParamsInspection */
        $steps->add('invalid.name', $this->getStepMock());
    }
} 