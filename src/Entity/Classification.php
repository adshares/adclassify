<?php

namespace Adshares\Adclassify\Entity;

use Adshares\Adclassify\Entity\Traits\BlameableEntity;
use Adshares\Adclassify\Entity\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Adshares\Adclassify\Repository\ClassificationRepository")
 * @ORM\Table(
 *     name="classification",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="checksum_idx", columns={"checksum"})},
 *     indexes={
 *         @ORM\Index(name="created_by_idx", columns={"created_by_id"}),
 *         @ORM\Index(name="updated_by_idx", columns={"updated_by_id"})
 *     }
 * )
 * @UniqueEntity(fields={"checksum"}, message="There is already a classification with this checksum")
 * @Gedmo\Loggable
 */
class Classification
{
    use TimestampableEntity, BlameableEntity;

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
     * @ORM\Column(type="binary", length=20)
     * @Assert\NotBlank()
     */
    private $checksum;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\NotBlank()
     */
    private $processed = false;

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

    public function getChecksum(): string
    {
        if (gettype($this->checksum) === 'resource') {
            $this->checksum = stream_get_contents($this->checksum);
        }
        return $this->checksum;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): void
    {
        $this->processed = $processed;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function __toString(): string
    {
        return $this->checksum;
    }
}
