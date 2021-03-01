<?php

namespace Crm\OnboardingModule\Scenarios;

use Crm\ApplicationModule\Criteria\ScenarioParams\StringLabeledArrayParam;
use Crm\ApplicationModule\Criteria\ScenariosCriteriaInterface;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Localization\ITranslator;

class OnboardingGoalCompletedCriteria implements ScenariosCriteriaInterface
{
    public const KEY = 'onboarding-goal-completed';

    private $onboardingGoalsRepository;

    private $translator;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        ITranslator $translator
    ) {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->translator = $translator;
    }

    public function params(): array
    {
        $pairs = $this->onboardingGoalsRepository->all()->order('name ASC')->fetchPairs('code', 'name');
        return [
            new StringLabeledArrayParam(self::KEY, $this->label(), $pairs),
        ];
    }

    public function addConditions(Selection $selection, array $paramValues, IRow $criterionItemRow): bool
    {
        $values = $paramValues[self::KEY];
        $selection
            ->where(':user_onboarding_goals.onboarding_goal.code IN (?)', $values->selection)
            ->where(':user_onboarding_goals.completed_at IS NOT NULL');
        return true;
    }

    public function label(): string
    {
        return $this->translator->translate('onboarding.scenarios.criteria.completed_goal.label');
    }
}
