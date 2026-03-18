<?php

namespace App\Entity;

use App\Repository\ReportTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportTagRepository::class)]
class ReportTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, NewsReport>
     */
    #[ORM\OneToMany(targetEntity: NewsReport::class, mappedBy: 'tag')]
    private Collection $newsReports;

    public function __construct()
    {
        $this->newsReports = new ArrayCollection();
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

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    public function setRelation(string $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * @return Collection<int, NewsReport>
     */
    public function getNewsReports(): Collection
    {
        return $this->newsReports;
    }

    public function addNewsReport(NewsReport $newsReport): static
    {
        if (!$this->newsReports->contains($newsReport)) {
            $this->newsReports->add($newsReport);
            $newsReport->setTag($this);
        }

        return $this;
    }

    public function removeNewsReport(NewsReport $newsReport): static
    {
        if ($this->newsReports->removeElement($newsReport)) {
            // set the owning side to null (unless already changed)
            if ($newsReport->getTag() === $this) {
                $newsReport->setTag(null);
            }
        }

        return $this;
    }
}
