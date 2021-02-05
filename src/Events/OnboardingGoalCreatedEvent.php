<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\IRow;

class OnboardingGoalCreatedEvent extends AbstractEvent
{
    private $onboardingGoal;

    public function __construct(IRow $onboardingGoal)
    {
        $this->onboardingGoal = $onboardingGoal;
    }

    public function getOnboardingGoal(): IRow
    {
        return $this->onboardingGoal;
    }
}
