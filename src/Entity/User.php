<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table("users",
 *      indexes={
 *          @ORM\Index(columns={"created_at"}),
 *          @ORM\Index(columns={"is_confirmed"}),
 *          @ORM\Index(columns={"is_witness"}),
 *      },
 * )
 */
class User implements UserInterface
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;

    /**
     * Старшина
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="subordinates_users", cascade={"persist"})
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
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected $first_name;

    /**
     * Отчество
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected $last_name;

    /**
     * Фамилия
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected $patronymic;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     * @Assert\Length(min = 4, minMessage = "Code length must be at least {{ limit }} characters long")
     * @Assert\Length(max = 4, minMessage = "Code length must be at least {{ limit }} characters long")
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected $passport_code;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_confirmed;

    /**
     * Является заверителем?
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $is_witness;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=4, nullable=true)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected $birth_year;

    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", precision=10, scale=8, nullable=true)
     */
    protected $latitude;

    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    protected $longitude;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->approved_users     = new ArrayCollection();
        $this->subordinates_users = new ArrayCollection();
        $this->created_at         = new \DateTime();
        $this->is_confirmed       = false;
        $this->is_witness         = false;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getFirstName().' '. (string) $this->getLastName();
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
     * @param string $provider
     *
     * @return UserOauth|null
     */
    public function getOauthByProvider(string $provider): ?UserOauth
    {
        foreach ($this->oauths as $oauth) {
            if ($oauth->getProvider() == $provider) {
                return $oauth;
            }
        }

        return null;
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
    public function getFirstName(): ?string
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

    /**
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->latitude ? (float) $this->latitude : null;
    }

    /**
     * @param float|null $latitude
     *
     * @return $this
     */
    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->longitude ? (float) $this->longitude : null;
    }

    /**
     * @param float|null $longitude
     *
     * @return $this
     */
    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return bool
     */
    public function isIsWitness(): bool
    {
        return $this->is_witness;
    }

    /**
     * @param bool $is_witness
     *
     * @return $this
     */
    public function setIsWitness(bool $is_witness): self
    {
        $this->is_witness = $is_witness;

        return $this;
    }
}
