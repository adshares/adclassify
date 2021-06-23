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
 *         @ORM\Index(name="ad_idx", columns={"ad_id"})
 *     }
 * )
 */
class Request
{
    use TimestampableEntity;

    public const STATUS_PROCESSED = 0;
    public const STATUS_NEW = 1;
    public const STATUS_PENDING = 2;
    public const STATUS_FAILED = 3;
    public const STATUS_REJECTED = 4;
    public const STATUS_CANCELED = 5;

    public const CALLBACK_SUCCESS = 0;
    public const CALLBACK_PENDING = 1;
    public const CALLBACK_FAILED = 2;

    private $streams = [];

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
     * @var Ad
     *
     * @ORM\ManyToOne(targetEntity="Ad")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank()
     */
    private $ad;

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
     * @ORM\Column(type="string", length=16)
     * @Assert\NotBlank()
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     * @Assert\NotBlank()
     */
    private $landingUrl;

    /**
     * @var resource
     *
     * @ORM\Column(type="binary", length=16)
     * @Assert\NotBlank()
     */
    private $campaignId;

    /**
     * @var resource
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

    /**
     * @var int
     *
     * @ORM\Column(type="integer", length=3, nullable=true)
     */
    private $callbackStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sentAt;

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

    public function setAd(Ad $ad): void
    {
        $this->ad = $ad;
    }

    public function getAd(): Ad
    {
        return $this->ad;
    }

    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    public function setServeUrl(string $serveUrl): void
    {
        $this->serveUrl = $serveUrl;
    }

    public function getServeUrl(): string
    {
        return $this->serveUrl;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setLandingUrl(string $landingUrl): void
    {
        $this->landingUrl = $landingUrl;
    }

    public function getLandingUrl(): string
    {
        return $this->landingUrl;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->streams['campaignId'] = $campaignId;
    }

    public function getCampaignId(): string
    {
        if (!isset($this->streams['campaignId']) && gettype($this->campaignId) === 'resource') {
            $this->streams['campaignId'] = stream_get_contents($this->campaignId);
        }
        return $this->streams['campaignId'] ?? $this->campaignId;
    }

    public function setBannerId(string $bannerId): void
    {
        $this->bannerId = $bannerId;
        $this->streams['bannerId'] = $bannerId;
    }

    public function getBannerId(): string
    {
        if (!isset($this->streams['bannerId']) && gettype($this->bannerId) === 'resource') {
            $this->streams['bannerId'] = stream_get_contents($this->bannerId);
        }
        return $this->streams['bannerId'] ?? $this->bannerId;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
        if (in_array($status, [self::STATUS_PROCESSED, self::STATUS_FAILED, self::STATUS_REJECTED])) {
            $this->callbackStatus  = self::CALLBACK_PENDING;
            $this->sentAt = null;
        }
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return $this->status == self::STATUS_PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->status == self::STATUS_PROCESSED;
    }

    public function isFailed(): bool
    {
        return $this->status == self::STATUS_FAILED;
    }

    public function isRejected(): bool
    {
        return $this->status == self::STATUS_REJECTED;
    }

    public function setInfo(?string $info): void
    {
        $this->info = $info;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setCallbackStatus(?int $callbackStatus): void
    {
        $this->callbackStatus = $callbackStatus;
    }

    public function getCallbackStatus(): ?int
    {
        return $this->callbackStatus;
    }

    public function isSent(): bool
    {
        return $this->callbackStatus === self::CALLBACK_SUCCESS;
    }

    public function setSentAt(?\DateTime $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    public function __toString(): string
    {
        return $this->bannerId;
    }
}
