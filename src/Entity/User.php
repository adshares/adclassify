<?php

namespace Adshares\Adclassify\Entity;

use Adshares\Adclassify\Entity\Traits\SoftDeleteableEntity;
use Adshares\Adclassify\Entity\Traits\TimestampableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Adshares\Adclassify\Repository\UserRepository")
 * @ORM\Table(
 *     name="user",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="email_idx", columns={"email"})}
 * )
 * @UniqueEntity(fields={"email"}, message="There is already an account with this e-mail")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class User implements UserInterface, \Serializable
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $fullName;

    /**
     * @ORM\OneToMany(targetEntity="ApiKey", mappedBy="user", cascade={"persist", "remove"})
     */
    private $apiKeys;

    public function __construct()
    {
        $this->apiKeys = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @return Collection|ApiKey[]
     */
    public function getApiKeys(): Collection
    {
        return $this->apiKeys;
    }

    public function addApiKey(ApiKey $apiKey): void
    {
        $this->apiKeys->add($apiKey);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string
    {
        // See "Do you need to use a Salt?" at https://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->email,
            $this->password,
            $this->roles,
            $this->fullName
        ]);
    }

    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->email,
            $this->password,
            $this->roles,
            $this->fullName
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function __toString(): string
    {
        return $this->fullName;
    }
}
