<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;

class UserOnboardingGoalTimedoutEvent extends AbstractEvent
{
    use UserOnboardingGoalEventTrait;
}
