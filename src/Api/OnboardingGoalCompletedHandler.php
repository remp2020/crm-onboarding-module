<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Api\JsonValidationTrait;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Http\Response;

class OnboardingGoalCompletedHandler extends ApiHandler
{
    use JsonValidationTrait;

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

    public function params(): array
    {
        return [];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\Response
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $result = $this->validateInput(__DIR__ . '/goal_completed.schema.json');
        if ($result->hasErrorResponse()) {
            return $result->getErrorResponse();
        }

        $json = $result->getParsedObject();
        $goalCode = $json->goal_code;
        $userId = $json->user_id;

        $goal = $this->onboardingGoalsRepository->findBy('code', $goalCode);
        if (!$goal) {
            $response = new JsonResponse(['status' => 'error', 'message' => "goal '$goalCode' not found"]);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $response = new JsonResponse(['status' => 'error', 'message' => "user with ID '$userId' not found"]);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $this->userOnboardingGoalsRepository->complete($userId, $goal->id);

        $response = new JsonResponse(['status' => 'ok']);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
