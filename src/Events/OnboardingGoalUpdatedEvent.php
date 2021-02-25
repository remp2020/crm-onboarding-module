<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\IRow;

class OnboardingGoalUpdatedEvent extends AbstractEvent
{
    private $onboardingGoal;

    /**
     * @param IRow $onboardingGoal Updated onboarding goal
     */
    public function __construct(IRow $onboardingGoal)
    {
        $this->onboardingGoal = $onboardingGoal;
    }

    public function getOnboardingGoal(): IRow
    {
        return $this->onboardingGoal;
    }
}
