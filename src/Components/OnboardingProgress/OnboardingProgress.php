<?php

namespace Crm\OnboardingModule\Components\OnboardingProgress;

use Crm\ApplicationModule\Models\Widget\WidgetInterface;
use Crm\OnboardingModule\Repositories\UserOnboardingGoalsRepository;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;

class OnboardingProgress extends Control implements WidgetInterface
{
    private $templateName = 'onboarding_progress.latte';

    private $translator;

    private $completedCount;

    private $userOnboardingGoalsRepository;

    public function __construct(
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository,
        Translator $translator
    ) {
        $this->translator = $translator;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
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
