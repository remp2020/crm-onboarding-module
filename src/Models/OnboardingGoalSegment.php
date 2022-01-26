<?php

namespace Crm\OnboardingModule\Models;

class OnboardingGoalSegment
{
    public static function getSegmentCode(string $onboardingGoalCode): string
    {
        return 'onboarding_' . $onboardingGoalCode;
    }

    public static function getSegmentName(string $onboardingGoalName): string
    {
        return 'Targeting onboarding goal: ' . $onboardingGoalName;
    }
}
