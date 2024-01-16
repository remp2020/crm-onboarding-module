<?php

namespace Crm\OnboardingModule;

use Crm\ApiModule\Models\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Models\Authorization\BearerTokenAuthorization;
use Crm\ApiModule\Models\Router\ApiIdentifier;
use Crm\ApiModule\Models\Router\ApiRoute;
use Crm\ApplicationModule\Criteria\ScenariosCriteriaStorage;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Event\EventsStorage;
use Crm\ApplicationModule\Event\LazyEventEmitter;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\ApplicationModule\SeederManager;
use Crm\ApplicationModule\Widget\LazyWidgetManagerInterface;
use Crm\OnboardingModule\Api\OnboardingGoalCompletedHandler;
use Crm\OnboardingModule\Api\OnboardingGoalsListHandler;
use Crm\OnboardingModule\Components\OnboardingProgress\OnboardingProgress;
use Crm\OnboardingModule\Events\OnboardingGoalCreatedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalCreatedEventHandler;
use Crm\OnboardingModule\Events\OnboardingGoalUpdatedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalUpdatedEventHandler;
use Crm\OnboardingModule\Scenarios\OnboardingGoalCompletedCriteria;
use Crm\OnboardingModule\Seeders\SegmentsSeeder;

class OnboardingModule extends CrmModule
{
    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'onboarding-goals', 'complete'), OnboardingGoalCompletedHandler::class, BearerTokenAuthorization::class)
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'onboarding-goals', 'list'), OnboardingGoalsListHandler::class, BearerTokenAuthorization::class)
        );
    }

    public function registerAdminMenuItems(MenuContainerInterface $menuContainer)
    {
        $internalMenu = new MenuItem('', '#', 'fa fa-th-large', 890);

        $menuItem = new MenuItem(
            $this->translator->translate('onboarding.menu.onboarding_goals'),
            ':Onboarding:OnboardingGoalsAdmin:',
            'fa fa-check',
            900
        );
        $menuContainer->attachMenuItemToForeignModule(':Users:UsersAdmin:', $internalMenu, $menuItem);
    }

    public function registerEvents(EventsStorage $eventsStorage)
    {
        $eventsStorage->register('onboarding_goal_created', OnboardingGoalCreatedEvent::class);
    }

    public function registerLazyEventHandlers(LazyEventEmitter $emitter)
    {
        $emitter->addListener(
            OnboardingGoalCreatedEvent::class,
            OnboardingGoalCreatedEventHandler::class
        );
        $emitter->addListener(
            OnboardingGoalUpdatedEvent::class,
            OnboardingGoalUpdatedEventHandler::class
        );
    }

    public function registerScenariosCriteria(ScenariosCriteriaStorage $scenariosCriteriaStorage)
    {
        $scenariosCriteriaStorage->register(
            'user',
            OnboardingGoalCompletedCriteria::KEY,
            $this->getInstance(OnboardingGoalCompletedCriteria::class)
        );
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(SegmentsSeeder::class));
    }

    public function registerLazyWidgets(LazyWidgetManagerInterface $widgetManager)
    {
        $widgetManager->registerWidget(
            'admin.user.detail.bottom',
            OnboardingProgress::class,
            1500
        );
    }
}
