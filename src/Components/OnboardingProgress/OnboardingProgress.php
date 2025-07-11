<?php

namespace Crm\OnboardingModule\Components\OnboardingProgress;

use Crm\ApplicationModule\Models\Widget\BaseLazyWidget;
use Crm\ApplicationModule\Models\Widget\DetailWidgetInterface;
use Crm\ApplicationModule\Models\Widget\LazyWidgetManager;
use Crm\OnboardingModule\Repositories\UserOnboardingGoalsRepository;
use Nette\Localization\Translator;

class OnboardingProgress extends BaseLazyWidget implements DetailWidgetInterface
{
    private $templateName = 'onboarding_progress.latte';

    private $translator;

    private $completedCount;

    private $userOnboardingGoalsRepository;

    public function __construct(
        LazyWidgetManager $lazyWidgetManager,
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository,
        Translator $translator,
    ) {
        parent::__construct($lazyWidgetManager);
        $this->translator = $translator;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
    }

    public function header($id = ''): string
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
