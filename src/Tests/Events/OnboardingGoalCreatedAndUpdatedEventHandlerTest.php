<?php
namespace Crm\OnboardingModule\Tests\Events;

use Crm\ApplicationModule\Event\LazyEventEmitter;
use Crm\OnboardingModule\Events\OnboardingGoalCreatedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalCreatedEventHandler;
use Crm\OnboardingModule\Events\OnboardingGoalUpdatedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalUpdatedEventHandler;
use Crm\OnboardingModule\Repositories\OnboardingGoalsRepository;
use Crm\OnboardingModule\Seeders\SegmentsSeeder;
use Crm\OnboardingModule\Tests\BaseTestCase;
use Crm\SegmentModule\Repository\SegmentsRepository;

class OnboardingGoalCreatedAndUpdatedEventHandlerTest extends BaseTestCase
{
    /** @var LazyEventEmitter */
    private $lazyEventEmitter;

    /** @var OnboardingGoalsRepository */
    private $onboardingGoalsRepository;

    /** @var SegmentsRepository */
    private $segmentsRepository;

    protected function requiredRepositories(): array
    {
        return [
            OnboardingGoalsRepository::class,
            SegmentsRepository::class,
        ];
    }

    protected function requiredSeeders(): array
    {
        return [
            SegmentsSeeder::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // register listeners; we want to test if event was emitted from repository
        $this->lazyEventEmitter = $this->inject(LazyEventEmitter::class);
        $this->lazyEventEmitter->addListener(
            OnboardingGoalCreatedEvent::class,
            $this->inject(OnboardingGoalCreatedEventHandler::class)
        );
        $this->lazyEventEmitter->addListener(
            OnboardingGoalUpdatedEvent::class,
            $this->inject(OnboardingGoalUpdatedEventHandler::class)
        );

        $this->onboardingGoalsRepository = $this->getRepository(OnboardingGoalsRepository::class);
        $this->segmentsRepository = $this->getRepository(SegmentsRepository::class);
    }

    protected function tearDown(): void
    {
        $this->lazyEventEmitter->removeAllListeners(OnboardingGoalCreatedEvent::class);
        $this->lazyEventEmitter->removeAllListeners(OnboardingGoalUpdatedEvent::class);

        parent::tearDown();
    }

    public function testSuccess()
    {
        // create onboarding goal; repository emits event OnboardingGoalCreatedEvent
        $onboardingGoal = $this->onboardingGoalsRepository->add(
            'test_onboarding_goal_code',
            'test_onboarding_goal_name',
            OnboardingGoalsRepository::TYPE_SIMPLE
        );

        // tested within Crm\OnboardingModule\Tests\Seeders\SegmentsSeederTest
        $segmentProperties = SegmentsSeeder::generateOnboardingGoalSegmentProperties($onboardingGoal);

        $segmentCreated = $this->segmentsRepository->findByCode($segmentProperties['code']);
        $this->assertNotEmpty($segmentCreated);
        $this->assertEquals($segmentProperties['code'], $segmentCreated->code);
        $this->assertEquals($segmentProperties['name'], $segmentCreated->name);
        $this->assertEquals($segmentProperties['query_string'], $segmentCreated->query_string);
        $this->assertTrue((bool) $segmentCreated->locked);

        // test update
        $onboardingGoalUpdate = clone($onboardingGoal);
        $this->onboardingGoalsRepository->update($onboardingGoalUpdate, [
            'code' => 'changed_test_onboarding_goal_code',
            'name' => 'changed_test_onboarding_goal_name',
        ]);

        // code cannot be updated; name is updated
        $this->assertEquals($onboardingGoal['code'], $onboardingGoalUpdate['code']);
        $this->assertNotEquals($onboardingGoal['name'], $onboardingGoalUpdate['name']);

        // tested within Crm\OnboardingModule\Tests\Seeders\SegmentsSeederTest
        $segmentUpdatedProperties = SegmentsSeeder::generateOnboardingGoalSegmentProperties($onboardingGoalUpdate);

        // load updated segment & compare it with generated properties
        $segmentUpdated = $this->segmentsRepository->findByCode($segmentUpdatedProperties['code']);
        $this->assertNotEmpty($segmentUpdated);
        $this->assertEquals($segmentUpdatedProperties['code'], $segmentUpdated->code);
        $this->assertEquals($segmentUpdatedProperties['name'], $segmentUpdated->name);
        $this->assertEquals($segmentUpdatedProperties['query_string'], $segmentUpdated->query_string);
        $this->assertTrue((bool) $segmentUpdated->locked);

        // segment's code & query string should be same as before update
        // (code cannot be updated)
        $this->assertEquals($segmentCreated->code, $segmentUpdated->code);
        // (query string depends on generateOnboardingGoalSegmentProperties; tested within Crm\OnboardingModule\Tests\Seeders\SegmentsSeederTest)
        $this->assertEquals($segmentCreated->query_string, $segmentUpdated->query_string);

        // name was changed, because onboarding goal's name changed
        $this->assertNotEquals($segmentCreated->name, $segmentUpdated->name);
    }
}
