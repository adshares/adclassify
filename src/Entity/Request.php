<?php

namespace Adshares\Adclassify\Entity;

use Adshares\Adclassify\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Adshares\Adclassify\Repository\RequestRepository")
 * @ORM\Table(
 *     name="request",
 *     indexes={
 *         @ORM\Index(name="user_idx", columns={"user_id"}),
 *         @ORM\Index(name="classification_idx", columns={"classification_id"})
 *     }
 * )
 */
class Request
{
    use TimestampableEntity;

    const STATUS_NEW = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PROCESSED = 0;

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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Classification")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private $classification;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank()
     */
    private $callbackUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank()
     */
    private $serveUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank()
     */
    private $landingUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="binary", length=16)
     * @Assert\NotBlank()
     */
    private $campaignId;

    /**
     * @var string
     *
     * @ORM\Column(type="binary", length=16)
     * @Assert\NotBlank()
     */
    private $bannerId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", length=3, options={"default": Adshares\Adclassify\Entity\Request::STATUS_NEW})
     */
    private $status = self::STATUS_NEW;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $info;

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

    public function getClassification(): User
    {
        return $this->classification;
    }

    public function setClassification(User $classification): void
    {
        $this->classification = $classification;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getServeUrl(): string
    {
        return $this->serveUrl;
    }

    public function setServeUrl(string $serveUrl): void
    {
        $this->serveUrl = $serveUrl;
    }

    public function getLandingUrl(): string
    {
        return $this->landingUrl;
    }

    public function setLandingUrl(string $landingUrl): void
    {
        $this->landingUrl = $landingUrl;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getBannerId(): string
    {
        return $this->bannerId;
    }

    public function setBannerId(string $bannerId): void
    {
        $this->bannerId = $bannerId;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): void
    {
        $this->info = $info;
    }

    public function __toString(): string
    {
        return $this->bannerId;
    }
}
