<?php

namespace Crm\ScenariosModule\Tests;

use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\PaymentsModule\Events\PaymentChangeStatusEvent;
use Crm\PaymentsModule\Events\PaymentStatusChangeHandler;
use Crm\PaymentsModule\PaymentItem\PaymentItemContainer;
use Crm\PaymentsModule\Repository\PaymentGatewaysRepository;
use Crm\PaymentsModule\Repository\PaymentsRepository;
use Crm\SubscriptionsModule\Builder\SubscriptionTypeBuilder;
use Crm\SubscriptionsModule\Generator\SubscriptionsGenerator;
use Crm\SubscriptionsModule\Generator\SubscriptionsParams;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Utils\DateTime;

class UserOnboardingGoalsRepositoryTest extends BaseTestCase
{
    /** @var UserManager */
    private $userManager;

    /** @var OnboardingGoalsRepository */
    private $onboardingGoalsRepository;

    /** @var UsersRepository */
    private $usersRepository;

    /** @var UserOnboardingGoalsRepository */
    private $userOnboardingGoalsRepository;

    /** @var SubscriptionTypeBuilder */
    private $subscriptionTypeBuilder;

    /** @var SubscriptionsGenerator */
    private $subscriptionGenerator;

    /** @var PaymentsRepository */
    private $paymentsRepository;

    private $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userManager = $this->inject(UserManager::class);
        $this->usersRepository = $this->inject(UsersRepository::class);
        $this->onboardingGoalsRepository = $this->getRepository(OnboardingGoalsRepository::class);
        $this->userOnboardingGoalsRepository = $this->getRepository(UserOnboardingGoalsRepository::class);
        $this->subscriptionTypeBuilder = $this->inject(SubscriptionTypeBuilder::class);
        $this->subscriptionGenerator = $this->inject(SubscriptionsGenerator::class);
        $this->paymentsRepository = $this->inject(PaymentsRepository::class);

        $pgr = $this->getRepository(PaymentGatewaysRepository::class);
        $this->paymentGateway = $pgr->add('test', 'test', 10, true, true);
    }

    public function testUserRegistrationAndSubscriptionOwnershipDistribution()
    {
        $subscriptionType = $this->subscriptionTypeBuilder
            ->createNew()
            ->setName('test_subscription')
            ->setUserLabel('')
            ->setActive(true)
            ->setPrice(1)
            ->setLength(365)
            ->save();

        // Subscribers
        $user1 = $this->userWithRegDate('test1@example.com');
        $this->addSubscription($user1, $subscriptionType);
        $user2 = $this->userWithRegDate('test2@example.com');
        $this->addSubscription($user2, $subscriptionType);
        $user3 = $this->userWithRegDate('test3@example.com');
        $this->addSubscription($user3, $subscriptionType);
        $user4 = $this->userWithRegDate('test4@example.com');
        $this->addSubscription($user4, $subscriptionType);
        $user5 = $this->userWithRegDate('test5@example.com');
        $this->addSubscription($user5, $subscriptionType);

        // Non-subscribers
        $user6 = $this->userWithRegDate('test6@example.com');
        $user7 = $this->userWithRegDate('test7@example.com');
        $user8 = $this->userWithRegDate('test8@example.com');
        $user9 = $this->userWithRegDate('test9@example.com');
        $user10 = $this->userWithRegDate('test10@example.com');

        // Create goal
        $goal1 = $this->onboardingGoalsRepository->add('goal1', 'goal1', OnboardingGoalsRepository::TYPE_SIMPLE);

        // Complete goal - subscribers
        // Goal completed 2 days since reg
        $this->userOnboardingGoalsRepository->complete($user1->id, $goal1->id, new DateTime('2020-01-03 02:00:00'));
        $this->userOnboardingGoalsRepository->complete($user2->id, $goal1->id, new DateTime('2020-01-03 02:00:00'));
        // 0 days since reg
        $this->userOnboardingGoalsRepository->complete($user3->id, $goal1->id, new DateTime('2020-01-01 01:30:00'));
         //3-6 days since reg user4,
        $this->userOnboardingGoalsRepository->complete($user4->id, $goal1->id, new DateTime('2020-01-05 02:00:00'));
        // 31+ days since reg
        $this->userOnboardingGoalsRepository->complete($user5->id, $goal1->id, new DateTime('2020-02-05 01:00:00'));

        // Complete goal - non-subscribers
        // 1 days since reg
        $this->userOnboardingGoalsRepository->complete($user6->id, $goal1->id, new DateTime('2020-01-02 02:00:00'));
        $this->userOnboardingGoalsRepository->complete($user7->id, $goal1->id, new DateTime('2020-01-02 02:00:00'));
        // 7-30 days since reg
        $this->userOnboardingGoalsRepository->complete($user8->id, $goal1->id, new DateTime('2020-01-10 02:00:00'));
        $this->userOnboardingGoalsRepository->complete($user9->id, $goal1->id, new DateTime('2020-01-10 02:00:00'));
        $this->userOnboardingGoalsRepository->complete($user10->id, $goal1->id, new DateTime('2020-01-10 02:00:00'));

        // Load distributions
        $distributions = $this->userOnboardingGoalsRepository->userRegistrationAndSubscriptionOwnershipDistributionForGoal($goal1->id);

        $subscribers = [];
        $nonSubscribers = [];
        foreach ($distributions as $r) {
            if ($r->had_subscription) {
                $subscribers[$r->days_from_registration_range] = (int)$r->total;
            } else {
                $nonSubscribers[$r->days_from_registration_range] = (int)$r->total;
            }
        }

        $this->assertEquals(1, $subscribers['0']);
        $this->assertEquals(2, $subscribers['2']);
        $this->assertEquals(1, $subscribers['3-6']);
        $this->assertEquals(1, $subscribers['31+']);
        $this->assertEquals(2, $nonSubscribers['1']);
        $this->assertEquals(3, $nonSubscribers['7-30']);
    }

    public function testNonSubscribersAndFirstFollowingPaymentInDaysDistribution()
    {
        // To create subscriptions from payments, register listener
        $paymentStatusChangeHandler = $this->inject(PaymentStatusChangeHandler::class);
        $this->emitter->addListener(PaymentChangeStatusEvent::class, $paymentStatusChangeHandler);

        $subscriptionType = $this->subscriptionTypeBuilder
            ->createNew()
            ->setName('test_subscription')
            ->setUserLabel('')
            ->setActive(true)
            ->setPrice(1)
            ->setLength(365)
            ->save();

        // Create goal
        $goal = $this->onboardingGoalsRepository->add('goal1', 'goal1', OnboardingGoalsRepository::TYPE_SIMPLE);

        // Non-subscribers
        $user6 = $this->userWithRegDate('test6@example.com');
        $user7 = $this->userWithRegDate('test7@example.com');
        $user8 = $this->userWithRegDate('test8@example.com');
        $user9 = $this->userWithRegDate('test9@example.com');
        $user10 = $this->userWithRegDate('test10@example.com');

        $this->userOnboardingGoalsRepository->complete($user6->id, $goal->id, new DateTime('2020-01-02 01:00:00'));
        $this->userOnboardingGoalsRepository->complete($user7->id, $goal->id, new DateTime('2020-01-02 01:00:00'));
        $this->userOnboardingGoalsRepository->complete($user8->id, $goal->id, new DateTime('2020-01-02 01:00:00'));
        $this->userOnboardingGoalsRepository->complete($user9->id, $goal->id, new DateTime('2020-01-02 01:00:00'));
        $this->userOnboardingGoalsRepository->complete($user10->id, $goal->id, new DateTime('2020-01-02 01:00:00'));

        // Create payments
        // 0day - 1x
        $this->addPayment($user6, $subscriptionType, '2020-01-02 12:00:00');
        // 1day - 2x
        $this->addPayment($user7, $subscriptionType, '2020-01-03 01:00:00');
        $this->addPayment($user8, $subscriptionType, '2020-01-03 10:00:00');
        // 7-30 - 1x
        $this->addPayment($user9, $subscriptionType, '2020-01-15 01:00:00');
        // Never - 1x

        // Load distributions
        $distributions = $this->userOnboardingGoalsRepository->nonSubscribersAndFirstFollowingPaymentInDaysDistributionForGoal($goal->id);

        $daysCounts = [];
        foreach ($distributions as $r) {
            $daysCounts[$r->first_payment_in_days_range] = (int)$r->total;
        }

        $this->assertEquals(1, $daysCounts['0']);
        $this->assertEquals(2, $daysCounts['1']);
        $this->assertEquals(1, $daysCounts['7-30']);
        $this->assertEquals(1, $daysCounts['-']);
    }

    private function addSubscription($user, $subscriptionType, $startDateString = '2020-01-01 01:00:00', $endDateString = '2021-01-01 01:00:00')
    {
        $this->subscriptionGenerator->generate(new SubscriptionsParams(
            $subscriptionType,
            $user,
            SubscriptionsRepository::TYPE_REGULAR,
            new DateTime($startDateString),
            new DateTime($endDateString),
            true
        ), 1);
    }

    private function addPayment($user, $subscriptionType, $paidAtString, $startSubscriptionAtString = '2021-01-01 01:00:00')
    {
        $payment = $this->paymentsRepository->add($subscriptionType, $this->paymentGateway, $user, new PaymentItemContainer(), null, 1, new DateTime($startSubscriptionAtString));
        $this->paymentsRepository->updateStatus($payment, PaymentsRepository::STATUS_PAID);
        $this->paymentsRepository->update($payment, ['paid_at' => new DateTime($paidAtString)]);
    }

    private function userWithRegDate($email, $regDateString = '2020-01-01 01:00:00')
    {
        $user = $this->userManager->addNewUser($email, false, 'unknown', null, false);
        $this->usersRepository->update($user, ['created_at' => new DateTime($regDateString)]);
        return $user;
    }
}
