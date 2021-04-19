<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;

class UserOnboardingGoalCreatedEvent extends AbstractEvent
{
    use UserOnboardingGoalEventTrait;
}
