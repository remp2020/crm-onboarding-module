<?php

namespace Crm\OnboardingModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Authorization\BearerTokenAuthorization;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\Menu\MenuContainerInterface;
use Crm\ApplicationModule\Menu\MenuItem;
use Crm\OnboardingModule\Events\OnboardingGoalCompletedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalCompletedHandler;
use League\Event\Emitter;

class OnboardingModule extends CrmModule
{
    public function registerEventHandlers(Emitter $emitter)
    {
        $emitter->addListener(
            OnboardingGoalCompletedEvent::class,
            $this->getInstance(OnboardingGoalCompletedHandler::class)
        );
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'onboarding-goals', 'complete'), \Crm\OnboardingModule\Api\OnboardingGoalCompletedHandler::class, BearerTokenAuthorization::class)
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'onboarding-goals', 'list'), \Crm\OnboardingModule\Api\OnboardingGoalsListHandler::class, BearerTokenAuthorization::class)
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
}
