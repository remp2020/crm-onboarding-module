<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Params\InputParam;
use Crm\ApiModule\Params\ParamsProcessor;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Http\Response;

class OnboardingGoalCompletedHandler extends ApiHandler
{
    private $onboardingGoalsRepository;

    private $userOnboardingGoalsRepository;

    private $usersRepository;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        UsersRepository $usersRepository,
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository
    ) {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
        $this->usersRepository = $usersRepository;
    }

    public function params()
    {
        return [
            new InputParam(InputParam::TYPE_GET, 'goal_code', InputParam::REQUIRED),
            new InputParam(InputParam::TYPE_GET, 'user_id', InputParam::REQUIRED),
        ];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\IResponse
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $paramsProcessor = new ParamsProcessor($this->params());
        $error = $paramsProcessor->isError();
        if ($error) {
            $response = new JsonResponse(['status' => 'error', 'message' => $error]);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        }
        $params = $paramsProcessor->getValues();

        $goalCode = $params['goal_code'];
        $userId = $params['user_id'];

        $goal = $this->onboardingGoalsRepository->findBy('code', $goalCode);
        if (!$goal) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'goal not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'user not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $this->userOnboardingGoalsRepository->complete($userId, $goal->id);

        $response = new JsonResponse(['status' => 'ok']);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
