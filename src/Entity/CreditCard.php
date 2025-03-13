<?php

namespace App\Entity;

use App\Enum\CardTypeEnum;
use App\Repository\CreditCardRepository;
use App\Service\CreditCardEditService;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

#[ORM\Entity(repositoryClass: CreditCardRepository::class)]
#[ORM\Table(name: "credit_cards")]
class CreditCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', unique: true)]
    private ?int $externalId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'float')]
    private float $annualEquivalentRate = 0.0;

    #[ORM\Column(type: 'float')]
    private float $firstYearFee = 0.0;

    #[ORM\Column(type: 'float')]
    private float $annualCharges = 0.0;

    #[ORM\Column(type: 'boolean')]
    private bool $hasRewardProgram = false;

    #[ORM\Column(type: 'boolean')]
    private bool $hasInsurance = false;

    #[ORM\Column(type: 'float')]
    private float $rating = 0.0;

    #[ORM\Column(length: 10, enumType: CardTypeEnum::class)]
    private ?CardTypeEnum $cardType = CardTypeEnum::CREDIT;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'creditCards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bank $bank = null;

    #[ORM\OneToMany(targetEntity: CreditCardEdit::class, mappedBy: 'creditCard', orphanRemoval: true)]
    private Collection $edits;

    #[ORM\Column(type: 'boolean')]
    private bool $atmFreeDomestic = false;

    #[ORM\Column(type: 'text')]
    private ?string $information = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $provider = null;

    #[ORM\Column(type: 'float')]
    private float $incentiveAmount = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private ?CreditCardEditService $editService = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->edits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getAnnualEquivalentRate(): float
    {
        return $this->annualEquivalentRate;
    }

    public function setAnnualEquivalentRate(float $annualEquivalentRate): self
    {
        $this->annualEquivalentRate = $annualEquivalentRate;
        return $this;
    }

    public function getFirstYearFee(): float
    {
        return $this->firstYearFee;
    }

    public function setFirstYearFee(float $firstYearFee): self
    {
        $this->firstYearFee = $firstYearFee;
        return $this;
    }

    public function getAnnualCharges(): float
    {
        return $this->annualCharges;
    }

    public function setAnnualCharges(float $annualCharges): self
    {
        $this->annualCharges = $annualCharges;
        return $this;
    }

    public function hasRewardProgram(): bool
    {
        return $this->hasRewardProgram;
    }

    public function setHasRewardProgram(bool $hasRewardProgram): self
    {
        $this->hasRewardProgram = $hasRewardProgram;
        return $this;
    }

    public function hasInsurance(): bool
    {
        return $this->hasInsurance;
    }

    public function setHasInsurance(bool $hasInsurance): self
    {
        $this->hasInsurance = $hasInsurance;
        return $this;
    }

    public function getRating(): float
    {
        return $this->rating;
    }

    public function setRating(float $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getCardType(): ?CardTypeEnum
    {
        return $this->cardType;
    }

    public function setCardType(CardTypeEnum $cardType): static
    {
        $this->cardType = $cardType;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getBank(): ?Bank
    {
        return $this->bank;
    }

    public function setBank(?Bank $bank): static
    {
        $this->bank = $bank;
        return $this;
    }

    public function isCredit(): bool
    {
        return $this->cardType === CardTypeEnum::CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->cardType === CardTypeEnum::DEBIT;
    }

    public function getAtmFreeDomestic(): bool
    {
        return $this->atmFreeDomestic;
    }

    public function setAtmFreeDomestic(bool $atmFreeDomestic): self
    {
        $this->atmFreeDomestic = $atmFreeDomestic;
        return $this;
    }

    public function getInformation(): ?string
    {
        return $this->information;
    }

    public function setInformation(?string $information): self
    {
        $this->information = $information;
        return $this;
    }

    public function getProvider(): ?int
    {
        return $this->provider;
    }

    public function setProvider(?int $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getIncentiveAmount(): float
    {
        return $this->incentiveAmount;
    }

    public function setIncentiveAmount(float $incentiveAmount): self
    {
        $this->incentiveAmount = $incentiveAmount;
        return $this;
    }
}
