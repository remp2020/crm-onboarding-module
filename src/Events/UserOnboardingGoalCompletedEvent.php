<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;

class UserOnboardingGoalCompletedEvent extends AbstractEvent
{
    use UserOnboardingGoalEventTrait;
}
