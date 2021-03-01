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

    /** @var UserOnboardingGoalsRepository */
    private $userOnboardingGoalsRepository;

    /** @var UsersRepository */
    private $usersRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->onboardingGoalsRepository = $this->getRepository(OnboardingGoalsRepository::class);
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
        [$onboardingGoal, $user, $criteriaSelection, $onboardingGoalCompletedCriteria] = $this->prepare();
        // complete onboarding goal for user
        $this->userOnboardingGoalsRepository->complete($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // user passed criteria; user row is returned from selection
        $userPassedCriteria = $criteriaSelection->fetch();
        $this->assertNotFalse($userPassedCriteria);
        $this->assertEquals($userPassedCriteria->email, $user->email);
    }

    public function testGoalNotCompletedFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection, $onboardingGoalCompletedCriteria] = $this->prepare();

        // add active onboarding goal for user
        $this->userOnboardingGoalsRepository->add($user->id, $onboardingGoal->id);

        $this->assertTrue(
            $onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // false; user didn't pass criteria
        $this->assertFalse($criteriaSelection->fetch());
    }

    public function testGoalTimedoutFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection, $onboardingGoalCompletedCriteria] = $this->prepare();

        // timeout onboarding goal for user
        $this->userOnboardingGoalsRepository->timeout($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::KEY => (object)['selection' => [$onboardingGoal->code]]],
                $user
            )
        );

        // false; user didn't pass criteria
        $this->assertFalse($criteriaSelection->fetch());
    }


    public function testDifferentGoalCompletedFailure()
    {
        [$onboardingGoal, $user, $criteriaSelection, $onboardingGoalCompletedCriteria] = $this->prepare();

        // timeout onboarding goal for user
        $this->userOnboardingGoalsRepository->timeout($user->id, $onboardingGoal->id, new DateTime());

        $this->assertTrue(
            $onboardingGoalCompletedCriteria->addConditions(
                $criteriaSelection,
                [OnboardingGoalCompletedCriteria::KEY => (object)['selection' => ['different_not_completed_goal']]],
                $user
            )
        );

        // false; user didn't pass criteria
        $this->assertFalse($criteriaSelection->fetch());
    }

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
        $onboardingGoalCompletedCriteria = new OnboardingGoalCompletedCriteria(
            $this->onboardingGoalsRepository,
            $this->inject(ITranslator::class)
        );

        return [
            $onboardingGoal,
            $user,
            $criteriaSelection,
            $onboardingGoalCompletedCriteria
        ];
    }
}
