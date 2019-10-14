<?php

namespace Crm\OnboardingModule;

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
