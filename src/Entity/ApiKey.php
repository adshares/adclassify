<?php

namespace Adshares\Adclassify\Entity;

use Adshares\Adclassify\Entity\Traits\SoftDeleteableEntity;
use Adshares\Adclassify\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Adshares\Adclassify\Repository\ApiKeyRepository")
 * @ORM\Table(
 *     name="api_key",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="name_idx", columns={"name"})},
 *     indexes={@ORM\Index(name="user_idx", columns={"user_id"})}
 * )
 * @UniqueEntity(fields={"name"}, message="There is already an api key with this name")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 */
class ApiKey
{
    use TimestampableEntity, SoftDeleteableEntity;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apiKeys")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=22)
     * @Assert\NotBlank()
     */
    private $secret;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
