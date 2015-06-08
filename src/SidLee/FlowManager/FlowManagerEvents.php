<?php
namespace SidLee\FlowManager;

final class FlowManagerEvents
{
    const ASCERTAIN_STEP_ELIGIBILITY_EVENT = 'flow_manager.ascertain_step_eligibility';
    const PRE_HANDLE_REQUEST_EVENT = 'flow_manager.pre_handle_request';
    const POST_HANDLE_REQUEST_EVENT = 'flow_manager.post_handle_request';

    private function __construct()
    {

    }
}