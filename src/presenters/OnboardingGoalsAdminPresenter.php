<?php

namespace Crm\OnboardingModule\Presenters;

use Crm\AdminModule\Presenters\AdminPresenter;
use Crm\ApplicationModule\Components\VisualPaginator;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;

class OnboardingGoalsAdminPresenter extends AdminPresenter
{
    private $onboardingGoalsRepository;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository
    ) {
        parent::__construct();
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
    }

    public function renderDefault()
    {
        $onboardingGoals = $this->onboardingGoalsRepository->all();

        $vp = new VisualPaginator();
        $this->addComponent($vp, 'goals_vp');

        $paginator = $vp->getPaginator();
        $paginator->setItemCount((clone $onboardingGoals)->count('*'));
        $paginator->setItemsPerPage($this->onPage);

        $this->template->onboardingGoals = $onboardingGoals->limit($paginator->getLength(), $paginator->getOffset());
    }

    public function renderNew()
    {
    }

    public function renderShow($id)
    {
        //$funnel = $this->salesFunnelsRepository->find($id);
        //if (!$funnel) {
        //    $this->flashMessage($this->translator->translate('sales_funnel.admin.sales_funnels.messages.sales_funnel_not_found'), 'danger');
        //    $this->redirect('default');
        //}
        //$this->template->funnel = $funnel;
        //$this->template->total_paid_amount = $this->salesFunnelsRepository->totalPaidAmount($funnel);
        //$this->template->subscriptionTypesPaymentsMap = $this->salesFunnelsRepository->getSalesFunnelDistribution($funnel);
        //$this->template->meta = $this->salesFunnelsMetaRepository->all($funnel);
        //
        //$payments = $this->paymentsRepository->getTable()
        //    ->where(['status' => PaymentsRepository::STATUS_PAID, 'sales_funnel_id' => $funnel->id])
        //    ->order('paid_at DESC');
        //
        //$filteredCount = $this->template->filteredCount = $payments->count('*');
        //$vp = new VisualPaginator();
        //$this->addComponent($vp, 'paymentsvp');
        //$paginator = $vp->getPaginator();
        //$paginator->setItemCount($filteredCount);
        //$paginator->setItemsPerPage($this->onPage);
        //
        //$this->template->vp = $vp;
        //$this->template->payments = $payments->limit($paginator->getLength(), $paginator->getOffset());
    }

    public function renderEdit($id)
    {
        $this->template->funnel = $this->salesFunnelsRepository->find($id);
    }
}
