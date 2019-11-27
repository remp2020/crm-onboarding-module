<?php

namespace Crm\OnboardingModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\ActiveRow;
use Crm\ApplicationModule\Components\VisualPaginator;
use Crm\OnboardingModule\Forms\OnboardingGoalFormFactory;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use DateInterval;
use Nette\Utils\DateTime;

class OnboardingGoalsAdminPresenter extends AdminPresenter
{
    private $onboardingGoalsRepository;

    private $onboardingGoalFormFactory;

    private $userOnboardingGoalsRepository;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository,
        OnboardingGoalFormFactory $onboardingGoalFormFactory
    ) {
        parent::__construct();
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->onboardingGoalFormFactory = $onboardingGoalFormFactory;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
    }

    public function renderDefault()
    {
        $onboardingGoals = $this->onboardingGoalsRepository->all();

        $goalsLast24hours = $this->userOnboardingGoalsRepository
            ->completedGoalsCountSince((new DateTime())->sub(new DateInterval('P1D')));

        $goalsLast7days = $this->userOnboardingGoalsRepository
            ->completedGoalsCountSince((new DateTime())->sub(new DateInterval('P7D')));

        $goalsLast31days = $this->userOnboardingGoalsRepository
            ->completedGoalsCountSince((new DateTime())->sub(new DateInterval('P31D')));

        $vp = new VisualPaginator();
        $this->addComponent($vp, 'goals_vp');

        $paginator = $vp->getPaginator();
        $paginator->setItemCount((clone $onboardingGoals)->count('*'));
        $paginator->setItemsPerPage($this->onPage);

        $this->template->onboardingGoals = $onboardingGoals->limit($paginator->getLength(), $paginator->getOffset());
        $this->template->goalsLast24hours = $goalsLast24hours;
        $this->template->goalsLast7days = $goalsLast7days;
        $this->template->goalsLast31days = $goalsLast31days;
    }

    public function renderNew()
    {
    }

    public function renderShow($id)
    {
        $goal = $this->onboardingGoalsRepository->find($id);
        if (!$goal) {
            $this->flashMessage($this->translator->translate('onboarding.admin.onboarding_goals.messages.goal_not_found'));
            $this->redirect('default');
        }

        $this->template->goal = $goal;
    }

    public function renderEdit($id)
    {
        $this->template->goal = $this->onboardingGoalsRepository->find($id);
    }

    protected function createComponentOnboardingGoalForm()
    {
        $id = $this->getParameter('id');
        $form = $this->onboardingGoalFormFactory->create($id);

        $this->onboardingGoalFormFactory->onSave = function (ActiveRow $goal) {
            $this->flashMessage($this->translator->translate('onboarding.admin.onboarding_goals.messages.goal_created'));
            $this->redirect('Show', $goal->id);
        };
        $this->onboardingGoalFormFactory->onUpdate = function (ActiveRow $goal) {
            $this->flashMessage($this->translator->translate('onboarding.admin.onboarding_goals.messages.goal_updated'));
            $this->redirect('Show', $goal->id);
        };

        return $form;
    }
}
