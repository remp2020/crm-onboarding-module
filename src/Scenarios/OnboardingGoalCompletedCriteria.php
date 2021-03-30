<?php

namespace Crm\OnboardingModule\Scenarios;

use Crm\ApplicationModule\Criteria\ScenarioParams\StringLabeledArrayParam;
use Crm\ApplicationModule\Criteria\ScenarioParams\TimeframeParam;
use Crm\ApplicationModule\Criteria\ScenariosCriteriaInterface;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Localization\ITranslator;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

class OnboardingGoalCompletedCriteria implements ScenariosCriteriaInterface
{
    public const KEY = 'onboarding-goal-completed';

    public const GOALS_KEY = 'onboarding-goals';
    public const TIMEFRAME_KEY = 'onboarding-goal-timeframe';

    public const OPERATOR_IN_THE_LAST = 'in the last';
    public const OPERATOR_BEFORE = 'before';
    private const OPERATORS = [
        '>=' => self::OPERATOR_IN_THE_LAST,
        '<=' => self::OPERATOR_BEFORE,
    ];

    private const UNITS = ['days', 'weeks', 'months', 'years'];

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
        $onboardingGoals = $this->onboardingGoalsRepository->all()->order('name ASC')->fetchPairs('code', 'name');
        return [
            new StringLabeledArrayParam(
                self::GOALS_KEY,
                $this->translator->translate('onboarding.scenarios.criteria.completed_goal.goal_param.label'),
                $onboardingGoals
            ),
            new TimeframeParam(
                self::TIMEFRAME_KEY,
                $this->translator->translate('onboarding.scenarios.criteria.completed_goal.timeframe_param.label'),
                $this->translator->translate('onboarding.scenarios.criteria.completed_goal.timeframe_param.amount_label'),
                $this->translator->translate('onboarding.scenarios.criteria.completed_goal.timeframe_param.units_label'),
                array_values(self::OPERATORS),
                self::UNITS
            ),
        ];
    }

    public function addConditions(Selection $selection, array $paramValues, IRow $criterionItemRow): bool
    {
        $goals = $paramValues[self::GOALS_KEY];
        $selection
            ->where(':user_onboarding_goals.onboarding_goal.code IN (?)', $goals->selection)
            ->where(':user_onboarding_goals.completed_at IS NOT NULL');

        if (isset(
            $paramValues[self::TIMEFRAME_KEY],
            $paramValues[self::TIMEFRAME_KEY]->operator,
            $paramValues[self::TIMEFRAME_KEY]->unit,
            $paramValues[self::TIMEFRAME_KEY]->selection
        )) {
            $timeframeOperator = array_search($paramValues[self::TIMEFRAME_KEY]->operator, self::OPERATORS, true);
            if ($timeframeOperator === false) {
                throw new \Exception("Timeframe operator [{$timeframeOperator}] is not a valid operator out of: " . Json::encode(array_values(self::OPERATORS)));
            }
            $timeframeUnit = $paramValues[self::TIMEFRAME_KEY]->unit;
            if (!in_array($timeframeUnit, self::UNITS, true)) {
                throw new \Exception("Timeframe unit [{$timeframeUnit}] is not a valid unit out of: " . Json::encode(self::UNITS));
            }
            $timeframeValue = $paramValues[self::TIMEFRAME_KEY]->selection;
            if (filter_var($timeframeValue, FILTER_VALIDATE_INT, array("options" => array("min_range"=> 0))) === false) {
                throw new \Exception("Timeframe value [{$timeframeValue}] is not a valid value. It has to be positive integer.");
            }

            $completedAt = (new DateTime())->modify('-' . $timeframeValue . $timeframeUnit);
            $selection->where(':user_onboarding_goals.completed_at ' . $timeframeOperator . ' ? ', $completedAt);
        }

        return true;
    }

    public function label(): string
    {
        return $this->translator->translate('onboarding.scenarios.criteria.completed_goal.label');
    }
}
