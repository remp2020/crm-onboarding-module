<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\IRow;

class OnboardingGoalUpdatedEvent extends AbstractEvent
{
    private $onboardingGoal;

    private $originalCode;

    /**
     * @param string $originalCode Code of onboarding goal before update
     * @param IRow $onboardingGoal Updated onboarding goal
     */
    public function __construct(string $originalCode, IRow $onboardingGoal)
    {
        $this->onboardingGoal = $onboardingGoal;
        $this->originalCode = $originalCode;
    }

    public function getOnboardingGoal(): IRow
    {
        return $this->onboardingGoal;
    }

    public function getOriginalCode(): string
    {
        return $this->originalCode;
    }
}
