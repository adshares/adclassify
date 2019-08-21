<?php

namespace Adshares\Adclassify\Entity;

use Adshares\Adclassify\Entity\Traits\BlameableEntity;
use Adshares\Adclassify\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AdRepository")
 * @ORM\Table(
 *     name="ad",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="checksum_idx", columns={"checksum"})},
 *     indexes={
 *         @ORM\Index(name="created_by_idx", columns={"created_by_id"}),
 *         @ORM\Index(name="updated_by_idx", columns={"updated_by_id"})
 *     }
 * )
 * @UniqueEntity(fields={"checksum"}, message="There is already a classification with this checksum")
 * @Gedmo\Loggable
 */
class Ad
{
    use TimestampableEntity, BlameableEntity;

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
     * @var string|resource
     *
     * @ORM\Column(type="blob", nullable=true)
     */
    private $content;

    /**
     * @var resource
     *
     * @ORM\Column(type="binary", length=20)
     * @Assert\NotBlank()
     */
    private $checksum;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     */
    private $size;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\NotBlank()
     */
    private $processed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\NotBlank()
     */
    private $rejected = false;

    /**
     * @var array
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $keywords = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        if (!isset($this->streams['content']) && gettype($this->content) === 'resource') {
            $this->streams['content'] = stream_get_contents($this->content);
        }
        return $this->streams['content'] ?? $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
        $this->streams['content'] = $content;
    }

    public function getChecksum(): string
    {
        if (!isset($this->streams['checksum']) && gettype($this->checksum) === 'resource') {
            $this->streams['checksum'] = stream_get_contents($this->checksum);
        }
        return $this->streams['checksum'] ?? $this->checksum;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
        $this->streams['checksum'] = $checksum;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): void
    {
        $this->size = $size;
    }

    public function getWidth(): int
    {
        return explode('x', $this->size)[0];
    }

    public function getHeight(): int
    {
        return explode('x', $this->size)[1];
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function setRejected(bool $rejected): void
    {
        $this->rejected = $rejected;
        if ($rejected) {
            $this->keywords = null;
        }
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function __toString(): string
    {
        return $this->checksum;
    }
}
