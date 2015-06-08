<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\FlattenedFlow;
use SidLee\FlowManager\StepCollection;

class FlattenedFlowConstructorTest extends FlowTestCase
{
    public function testCompileFlowSingleLevel()
    {
        /** @var StepCollection $steps */
        $steps = new StepCollection();
        $steps->add('level1_step1', $this->getStepMock());
        $steps->add('level1_step2', $this->getStepMock());

        $flowManager = $this->getFlowManagerMock('root', $steps);
        $flattenedFlow = new FlattenedFlow($flowManager);

        /** @var string[] $expectedKeys */
        $expectedKeys = array('root.level1_step1', 'root.level1_step2');

        $this->assertStepNames($flattenedFlow, $expectedKeys);
    }

    public function testCompileFlowMultiLevels()
    {
        /** @var StepCollection $steps */
        $steps = new StepCollection();

        $steps->add('branch1', new StepCollection());
        $steps['branch1']->add('branch1_sublevel', new StepCollection());
        /** @noinspection PhpUndefinedMethodInspection */
        $steps['branch1']['branch1_sublevel']->add('sublevel_step1', $this->getStepMock());
        /** @noinspection PhpUndefinedMethodInspection */
        $steps['branch1']['branch1_sublevel']->add('sublevel_step2', $this->getStepMock());
        $steps['branch1']->add('branch1_step1', $this->getStepMock());

        $steps->add('branch2', new StepCollection());
        $steps['branch2']->add('branch2_step1', $this->getStepMock());
        $steps['branch2']->add('branch2_sublevel', new StepCollection());
        /** @noinspection PhpUndefinedMethodInspection */
        $steps['branch2']['branch2_sublevel']->add('sublevel_step1', $this->getStepMock());
        /** @noinspection PhpUndefinedMethodInspection */
        $steps['branch2']['branch2_sublevel']->add('sublevel_step2', $this->getStepMock());

        $flowManager = $this->getFlowManagerMock('root', $steps);
        $flattenedFlow = new FlattenedFlow($flowManager);

        $expectedFlow = array(
            'root.branch1.branch1_sublevel.sublevel_step1',
            'root.branch1.branch1_sublevel.sublevel_step2',
            'root.branch1.branch1_step1',
            'root.branch2.branch2_step1',
            'root.branch2.branch2_sublevel.sublevel_step1',
            'root.branch2.branch2_sublevel.sublevel_step2'
        );

        $this->assertStepNames($flattenedFlow, $expectedFlow);
    }
}