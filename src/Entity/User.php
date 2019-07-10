<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table("users",
 *      indexes={
 *          @ORM\Index(columns={"created_at"}),
 *          @ORM\Index(columns={"is_confirmed"}),
 *      },
 * )
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Старшина
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="children", cascade={"persist"})
     */
    protected $foreman;

    /**
     * Вписок подчинённых юзеров у старейшины.
     *
     * @var User[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="foreman", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $subordinates_users;

    /**
     * Заверитель
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="approved_users", cascade={"persist"})
     */
    protected $witness;

    /**
     * Вписок всеx заверенных юзеров.
     *
     * @var User[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="witness", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $approved_users;

    /**
     * @var UserOauth[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="UserOauth", mappedBy="user", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected $oauths;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_login_at;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $confirmed_at;

    /**
     * Имя
     *
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    protected $first_name;

    /**
     * Отчество
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $last_name;

    /**
     * Фамилия
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $patronymic;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     */
    protected $passport_code;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_confirmed;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     */
    protected $birth_year;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->approved_users     = new ArrayCollection();
        $this->subordinates_users = new ArrayCollection();
        $this->created_at         = new \DateTime();
        $this->is_confirmed       = false;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    /**
     * @return User
     */
    public function getForeman(): User
    {
        return $this->foreman;
    }

    /**
     * @param User $foreman
     *
     * @return $this
     */
    public function setForeman($foreman): self
    {
        $this->foreman = $foreman;

        return $this;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getSubordinatesUsers()
    {
        return $this->subordinates_users;
    }

    /**
     * @param User[]|ArrayCollection $subordinates_users
     *
     * @return $this
     */
    public function setSubordinatesUsers($subordinates_users): self
    {
        $this->subordinates_users = $subordinates_users;

        return $this;
    }

    /**
     * @return User
     */
    public function getWitness(): User
    {
        return $this->witness;
    }

    /**
     * @param User $witness
     *
     * @return $this
     */
    public function setWitness($witness): self
    {
        $this->witness = $witness;

        return $this;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getApprovedUsers()
    {
        return $this->approved_users;
    }

    /**
     * @param User[]|ArrayCollection $approved_users
     *
     * @return $this
     */
    public function setApprovedUsers($approved_users): self
    {
        $this->approved_users = $approved_users;

        return $this;
    }

    /**
     * @return UserOauth[]|ArrayCollection
     */
    public function getOauths()
    {
        return $this->oauths;
    }

    /**
     * @param UserOauth[]|ArrayCollection $oauths
     *
     * @return $this
     */
    public function setOauths($oauths): self
    {
        $this->oauths = $oauths;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getConfirmedAt(): ?\DateTime
    {
        return $this->confirmed_at;
    }

    /**
     * @param \DateTime $confirmed_at
     *
     * @return $this
     */
    public function setConfirmedAt($confirmed_at): self
    {
        $this->confirmed_at = $confirmed_at;

        return $this;
    }

    /**
     * @return string
     */
    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    /**
     * @param string $patronymic
     *
     * @return $this
     */
    public function setPatronymic($patronymic): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsConfirmed(): bool
    {
        return $this->is_confirmed;
    }

    /**
     * @param bool $is_confirmed
     *
     * @return $this
     */
    public function setIsConfirmed($is_confirmed): self
    {
        $this->is_confirmed = $is_confirmed;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBirthYear(): ?int
    {
        return $this->birth_year;
    }

    /**
     * @param int $birth_year
     *
     * @return $this
     */
    public function setBirthYear($birth_year): self
    {
        $this->birth_year = $birth_year;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPassportCode(): ?int
    {
        return $this->passport_code;
    }

    /**
     * @param int|null $passport_code
     *
     * @return $this
     */
    public function setPassportCode($passport_code): self
    {
        $this->passport_code = $passport_code;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     *
     * @return $this
     */
    public function setFirstName($first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     *
     * @return $this
     */
    public function setLastName($last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Returns the roles granted to the user.
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * Returns the password used to authenticate the user.
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     * @return string The password
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     * This can return null if the password was not encoded using a salt.
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    /**
     * Removes sensitive data from the user.
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLoginAt(): ?\DateTime
    {
        return $this->last_login_at;
    }

    /**
     * @param \DateTime|null $last_login_at
     *
     * @return $this
     */
    public function setLastLoginAt($last_login_at): self
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }
}
