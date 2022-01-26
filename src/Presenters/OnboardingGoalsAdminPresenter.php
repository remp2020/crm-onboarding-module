<?php

namespace Crm\OnboardingModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\ActiveRow;
use Crm\ApplicationModule\Components\Graphs\GoogleLineGraphGroupControlFactoryInterface;
use Crm\ApplicationModule\Components\Graphs\GoogleSankeyGraphGroupControlFactoryInterface;
use Crm\ApplicationModule\Components\VisualPaginator;
use Crm\ApplicationModule\Graphs\Criteria;
use Crm\ApplicationModule\Graphs\GraphDataItem;
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

    /** @persistent */
    public $dateFrom;

    /** @persistent */
    public $dateTo;

    public $goalId;

    public function startup()
    {
        parent::startup();
        $this->dateFrom = $this->dateFrom ?? DateTime::from('-2 months')->format('Y-m-d');
        $this->dateTo = $this->dateTo ?? DateTime::from('today')->format('Y-m-d');
    }

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

    /**
     * @admin-access-level read
     */
    public function renderDefault()
    {
        $onboardingGoals = $this->onboardingGoalsRepository->all()->order('created_at DESC');

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

    /**
     * @admin-access-level write
     */
    public function renderNew()
    {
    }

    /**
     * @admin-access-level read
     */
    public function renderShow($id)
    {
        $goal = $this->onboardingGoalsRepository->find($id);
        if (!$goal) {
            $this->flashMessage($this->translator->translate('onboarding.admin.onboarding_goals.messages.goal_not_found'));
            $this->redirect('default');
        }

        $this->goalId = $goal->id;
        $this->template->goal = $goal;
        $this->template->dateFrom = $this->dateFrom;
        $this->template->dateTo = $this->dateTo;
    }

    /**
     * @admin-access-level write
     */
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

    public function createComponentGoogleGoalCompletionCountGraph(GoogleLineGraphGroupControlFactoryInterface $factory)
    {
        $graphDataItem = new GraphDataItem();
        $graphDataItem->setCriteria((new Criteria())
            ->setTableName('user_onboarding_goals')
            ->setTimeField('created_at')
            ->setWhere('AND onboarding_goal_id=' . (int) $this->params['id'])
            ->setValueField('COUNT(*)')
            ->setStart(DateTime::from($this->dateFrom))
            ->setEnd(DateTime::from($this->dateTo)));

        $control = $factory->create();
        $control->setGraphTitle($this->translator->translate('onboarding.admin.onboarding_goals.show.completion_graph_title'))
            ->setGraphHelp($this->translator->translate('onboarding.admin.onboarding_goals.show.completion_graph_help'))
            ->addGraphDataItem($graphDataItem);

        return $control;
    }

    public function createComponentRegistrationToGoalDistributionGraph(GoogleSankeyGraphGroupControlFactoryInterface $factory)
    {
        $goalId = (int) $this->params['id'];
        $graphRows = [];

        $subscriberCaption = $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.was_subscriber');
        $nonSubscriberCaption = $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.was_non_subscriber');

        $distribution = $this->userOnboardingGoalsRepository->userRegistrationAndSubscriptionOwnershipDistributionForGoal($goalId);
        foreach ($distribution as $row) {
            $text = $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.goal_completed_since', ['count' => $row->days_from_registration_range]);

            $graphRows[] = [
                $text,
                $row->had_subscription ? $subscriberCaption : $nonSubscriberCaption,
                (int) $row->total
            ];
        }

        $graph = $factory->create();
        $graph->setGraphHelp($this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.help_registration'));
        $graph->setGraphTitle($this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.title_registration'));
        $graph->setRows($graphRows);
        $graph->setColumnNames(
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.from'),
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.to'),
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.users_count')
        );
        return $graph;
    }

    public function createComponentNonsubscribersToPaymentDistributionGraph(GoogleSankeyGraphGroupControlFactoryInterface $factory)
    {
        $goalId = (int) $this->params['id'];

        $graphRows = [];
        $nonSubscriberCaption = $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.was_non_subscriber');

        $distribution = $this->userOnboardingGoalsRepository->nonSubscribersAndFirstFollowingPaymentInDaysDistributionForGoal($goalId);
        foreach ($distribution as $row) {
            $text = $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.first_payment_in_days', ['count' => $row->first_payment_in_days_range]);
            $neverPaidText =  $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.never_paid');

            $graphRows[] = [
                $nonSubscriberCaption,
                $row->first_payment_in_days_range === '-' ? $neverPaidText : $text,
                (int) $row->total
            ];
        }

        $graph = $factory->create();
        $graph->setGraphHelp($this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.help_first_payment'));
        $graph->setGraphTitle($this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.title_first_payment'));
        $graph->setRows($graphRows);
        $graph->setColumnNames(
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.from'),
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.to'),
            $this->translator->translate('onboarding.admin.onboarding_goals.show.flow_graph.users_count')
        );

        return $graph;
    }
}
