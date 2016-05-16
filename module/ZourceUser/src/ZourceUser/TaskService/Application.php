<?php
/**
 * This file is part of Zource. (https://github.com/zource/)
 *
 * @link https://github.com/zource/zource for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zource. (https://github.com/zource/)
 * @license https://raw.githubusercontent.com/zource/zource/master/LICENSE MIT
 */

namespace ZourceUser\TaskService;

use Doctrine\ORM\EntityManager;
use Zend\Crypt\Password\PasswordInterface;
use Zend\Math\Rand;
use ZourceUser\Entity\OAuthApplication;
use ZourceUser\V1\Rest\Account\AccountEntity;

class Application
{
    private $entityManager;
    private $crypter;

    public function __construct(EntityManager $entityManager, PasswordInterface $crypter)
    {
        $this->entityManager = $entityManager;
        $this->crypter = $crypter;
    }

    public function getApplication($clientId)
    {
        return $this->entityManager->getRepository(OAuthApplication::class)->find($clientId);
    }

    public function deleteApplication(OAuthApplication $application)
    {
        $this->entityManager->remove($application);
        $this->entityManager->flush($application);
    }

    public function getForAccount(AccountEntity $account)
    {
        $applications = $this->entityManager->getRepository(OAuthApplication::class)->findBy([
            'account' => $account,
        ]);

        return $applications;
    }

    public function createApplicationFromArray(AccountEntity $account, array $data)
    {
        $clientSecret = Rand::getString(64);
        
        $oauthApplication = new OAuthApplication($data['name'], $data['homepage']);
        $oauthApplication->setAccount($account);
        $oauthApplication->setClientSecret($this->crypter->create($clientSecret));
        $oauthApplication->setDescription($data['description']);
        $oauthApplication->setRedirectUri($data['authorizationCallback']);

        $this->entityManager->persist($oauthApplication);
        $this->entityManager->flush($oauthApplication);

        return $clientSecret;
    }
}
