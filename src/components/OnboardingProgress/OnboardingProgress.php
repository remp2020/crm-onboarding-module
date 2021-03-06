<?php

namespace Crm\OnboardingModule\Components;

use Crm\ApplicationModule\Widget\WidgetInterface;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;

class OnboardingProgress extends Control implements WidgetInterface
{
    private $templateName = 'onboarding_progress.latte';

    private $translator;

    private $completedCount;

    private $onboardingGoalsRepository;

    private $userOnboardingGoalsRepository;

    private $usersRepository;

    public function __construct(
        UsersRepository $usersRepository,
        OnboardingGoalsRepository $onboardingGoalsRepository,
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository,
        ITranslator $translator
    ) {
        parent::__construct();
        $this->translator = $translator;
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
        $this->usersRepository = $usersRepository;
    }

    public function header($id = '')
    {
        $header = $this->translator->translate('onboarding.component.onboarding_progress.header');
        if ($id) {
            $header .= ' <small>(' . $this->completedCount($id) . ')</small>';
        }
        return $header;
    }

    public function identifier()
    {
        return 'onboardingprogress';
    }

    private function completedCount($id)
    {
        if ($this->completedCount == null) {
            $this->completedCount = count($this->userOnboardingGoalsRepository->all($id, true));
        }
        return $this->completedCount;
    }

    public function render($id)
    {
        $userGoals = $this->userOnboardingGoalsRepository->all($id, true);
        $this->template->userGoals = $userGoals;
        $this->template->id = $id;
        $this->template->setFile(__DIR__ . '/' . $this->templateName);
        $this->template->render();
    }
}
