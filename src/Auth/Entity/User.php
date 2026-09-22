<?php

declare(strict_types=1);

namespace Blendhtml\Core\Auth\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'auth__users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 254,
        unique: true
    )]
    private string $email;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE
    )]
    private DateTimeImmutable $updatedAt;

    #[ORM\ManyToMany(
        targetEntity: Role::class,
        inversedBy: 'users'
    )]
    #[ORM\JoinTable(
        name: 'auth__user_roles',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'user_id',
                referencedColumnName: 'id',
                nullable: false,
                onDelete: 'CASCADE'
            ),
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'role_id',
                referencedColumnName: 'id',
                nullable: false,
                onDelete: 'CASCADE'
            ),
        ]
    )]
    private Collection $roles;

    public function __construct(
        string $email,
        DateTimeImmutable $now
    ) {
        $this->email = $email;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = $now;
    }

    public function getRoleEntities(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): bool
    {
        if ($this->roles->contains($role)) {
            return false;
        }

        $this->roles->add($role);

        return true;
    }

    public function removeRole(Role $role): bool
    {
        return $this->roles->removeElement($role);
    }

    public function roleNames(): array
    {
        $names = array_map(
            static fn(Role $role): string =>
                $role->getName(),
            $this->roles->toArray()
        );

        sort($names, SORT_STRING);

        return $names;
    }
}
