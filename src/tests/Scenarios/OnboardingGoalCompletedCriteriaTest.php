<?php

namespace Crm\OnboardingModule\Tests\Scenarios;

use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\OnboardingModule\Scenarios\OnboardingGoalCompletedCriteria;
use Crm\OnboardingModule\Tests\BaseTestCase;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Localization\ITranslator;
use Nette\Utils\DateTime;

class OnboardingGoalCompletedCriteriaTest extends BaseTestCase
{
    /** @var OnboardingGoalsRepository */
    private $onboardingGoalsRepository;

    /** @var OnboardingGoalCompletedCriteria */
    private $onboardingGoalCompletedCriteria;

    /** @var UserOnboardingGoalsRepository */
    private $userOnboardingGoalsRepository;

    /** @var UsersRepository */
    private $usersRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->onboardingGoalsRepository = $this->getRepository(OnboardingGoalsRepository::class);
        $this->onboardingGoalCompletedCriteria = new OnboardingGoalCompletedCriteria(
            $this->onboardingGoalsRepository,
            $this->inject(ITranslator::class)
        );
        $this->userOnboardingGoalsRepository = $this->getRepository(UserOnboardingGoalsRepository::class);
        $this->usersRepository = $this->getRepository(UsersRepository::class);
    }

    protected function requiredRepositories(): array
    {
        return array_merge(parent::requiredRepositories(), [
            OnboardingGoalsRepository::class,
            UserOnboardingGoalsRepository::class,
            UsersRepository::class,
        ]);
    }

    protected function requiredSeeders(): array
    {
        return [
        ];
    }

    public function testGoalCompletedSuccess()
    {
        [$onboardingGoal, $user, $criteriaSelection] = $this->prepare();
        // complete onboarding goal for user
        $this->userOnboardingGoalsRepository->complete($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $this->onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::GOALS_KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // user passed criteria; user row is returned from selection
        $userPassedCriteria = $criteriaSelection->fetch();
        $this->assertNotNull($userPassedCriteria);
        $this->assertEquals($userPassedCriteria->email, $user->email);
    }

    public function testGoalNotCompletedFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection] = $this->prepare();

        // add active onboarding goal for user
        $this->userOnboardingGoalsRepository->add($user->id, $onboardingGoal->id);

        $this->assertTrue(
            $this->onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::GOALS_KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // user failed criteria; no user is returned
        $this->assertNull($criteriaSelection->fetch());
    }

    public function testGoalTimedoutFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection] = $this->prepare();

        // timeout onboarding goal for user
        $this->userOnboardingGoalsRepository->timeout($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $this->onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::GOALS_KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // user failed criteria; no user is returned
        $this->assertNull($criteriaSelection->fetch());
    }


    public function testDifferentGoalCompletedFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection] = $this->prepare();

        // timeout onboarding goal for user
        $this->userOnboardingGoalsRepository->timeout($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $this->onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::GOALS_KEY => (object)['selection' => ['different_not_completed_goal']]],
                $user
            )
        );

        // user failed criteria; no user is returned
        $this->assertNull($criteriaSelection->fetch());
    }

    /*********************************************************************
     * Timeframe tests
     ********************************************************************/

    public function testGoalCompletedWeekAgoScenarioWantsLastMonthSuccess()
    {
        // goal completed last week; scenario wants completed within last month
        [$criteriaSelection, $user] = $this->prepareForTimeframeTest(
            '-1 week',
            [
                'selection' => 1,
                'operator' => OnboardingGoalCompletedCriteria::OPERATOR_IN_THE_LAST,
                'unit' => 'months',
            ]
        );

        // user passed criteria; user row is returned from selection
        $userPassedCriteria = $criteriaSelection->fetch();
        $this->assertNotNull($userPassedCriteria);
        $this->assertEquals($userPassedCriteria->email, $user->email);
    }

    public function testGoalCompletedTwoMonthsAgoScenarioWantsLastMonthFailure()
    {
        // goal completed 2 months ago; scenario wants last month
        [$criteriaSelection, $user] = $this->prepareForTimeframeTest(
            '-2 months',
            [
                'selection' => 1,
                'operator' => OnboardingGoalCompletedCriteria::OPERATOR_IN_THE_LAST,
                'unit' => 'months',
            ]
        );

        // user failed criteria; no user is returned
        $this->assertNull($criteriaSelection->fetch());
    }

    public function testGoalCompletedLastWeekScenarioWantsOlderThanMonthFailure()
    {
        // goal completed 1 week ago; scenario wants completed older than month
        [$criteriaSelection, $user] = $this->prepareForTimeframeTest(
            '-1 week',
            [
                'selection' => 1,
                'operator' => OnboardingGoalCompletedCriteria::OPERATOR_BEFORE,
                'unit' => 'months',
            ]
        );

        // user failed criteria; no user is returned
        $this->assertNull($criteriaSelection->fetch());
    }

    /*********************************************************************
     * Helper methods
     ********************************************************************/

    private function prepare()
    {
        $onboardingGoal = $this->onboardingGoalsRepository->add(
            'test_goal',
            'Test goal',
            OnboardingGoalsRepository::TYPE_SIMPLE
        );

        /** @var UserManager $userManager */
        $userManager = $this->inject(UserManager::class);
        $user = $userManager->addNewUser('test@example.com');

        // prepare criteria
        $criteriaSelection = $this->usersRepository->getTable();

        return [
            $onboardingGoal,
            $user,
            $criteriaSelection
        ];
    }

    private function prepareForTimeframeTest(string $goalCompletedAgo, array $timeframeSettings): array
    {
        [$onboardingGoal, $user, $criteriaSelection] = $this->prepare();

        // complete onboarding goal for user
        $this->userOnboardingGoalsRepository->complete(
            $user->id,
            $onboardingGoal->id,
            (new DateTime())->modify($goalCompletedAgo)
        );

        // set scenario condition to check if user completed goal within defined timeframe
        $this->assertTrue(
            $this->onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [
                    OnboardingGoalCompletedCriteria::GOALS_KEY => (object)['selection' => [$onboardingGoal->code]],
                    OnboardingGoalCompletedCriteria::TIMEFRAME_KEY => (object)$timeframeSettings,
                ],
                $user
            )
        );

        return [$criteriaSelection, $user];
    }
}
