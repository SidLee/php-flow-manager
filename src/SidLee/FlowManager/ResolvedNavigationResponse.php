<?php
namespace SidLee\FlowManager;

class ResolvedNavigationResponse extends NavigationResponse
{
    public function __construct(NavigationDirection $navigationDirection = null, $targetStepName = null, $wasDirect = false)
    {
        parent::__construct($navigationDirection, $targetStepName);
        $this->wasDirect = $wasDirect;
    }

    private $wasDirect = false;

    /**
     * @return boolean
     */
    public function getWasDirect()
    {
        return $this->wasDirect;
    }

    /**
     * @return NavigationResponse
     */
    public function toUnresolvedNavigationResponse()
    {
        if ($this->wasDirect) {
            return new NavigationResponse(NavigationDirection::DIRECT(), $this->getTargetStepName());
        }

        return new NavigationResponse($this->getNavigationDirection());
    }
} 