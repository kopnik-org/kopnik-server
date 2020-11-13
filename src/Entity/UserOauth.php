<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table("users_oauths",
 *      indexes={
 *          @ORM\Index(columns={"created_at"}),
 *          @ORM\Index(columns={"email"}),
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"identifier", "provider"}),
 *          @ORM\UniqueConstraint(columns={"email", "provider"}),
 *          @ORM\UniqueConstraint(columns={"access_token"}),
 *      }
 * )
 */
class UserOauth
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Email()
     */
    protected ?string $email;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="oauths", cascade={"persist"})
     */
    protected User $user;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected string $access_token;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $refresh_token;

    /**
     * @ORM\Column(type="bigint", nullable=false)
     */
    protected int $identifier;

    /**
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    protected string $provider;

    public function __construct()
    {
        $this->created_at   = new \DateTime();
        $this->provider     = 'vkontakte';
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    public function setAccessToken(string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(?string $refresh_token): self
    {
        $this->refresh_token = $refresh_token;

        return $this;
    }

    public function getIdentifier(): int
    {
        return (int) $this->identifier;
    }

    public function setIdentifier(int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
