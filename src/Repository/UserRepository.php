<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\ApiKey;
use Adshares\Adclassify\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserRepository extends ServiceEntityRepository
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;

    public function __construct(
        ManagerRegistry $registry,
        UserPasswordEncoderInterface $passwordEncoder,
        ApiKeyRepository $apiKeyRepository
    ) {
        parent::__construct($registry, User::class);
        $this->passwordEncoder = $passwordEncoder;
        $this->apiKeyRepository = $apiKeyRepository;
    }

    public function createUser(string $email, string $fullname, string $password, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFullName($fullname);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $user->setRoles($roles);

        $this->_em->persist($user);
        $this->_em->flush();

        $apiKey = $this->apiKeyRepository->createApiKey($user);
        $user->addApiKey($apiKey);

        return $user;
    }
}
