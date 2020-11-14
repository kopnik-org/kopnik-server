<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Smart\CoreBundle\Doctrine\ColumnTrait;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table("users",
 *      indexes={
 *          @ORM\Index(columns={"created_at"}),
 *          @ORM\Index(columns={"is_witness"}),
 *          @ORM\Index(columns={"latitude"}),
 *          @ORM\Index(columns={"longitude"}),
 *          @ORM\Index(columns={"rank"}),
 *          @ORM\Index(columns={"kopnik_role"}),
 *          @ORM\Index(columns={"status"}),
 *      },
 * )
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="UserClosure")
 */
class User implements UserInterface
{
    use ColumnTrait\Id;
    use ColumnTrait\CreatedAt;

    const ROLE_KOPNIK           = 1; // Копный муж
    const ROLE_DANILOV_KOPNIK   = 2; // Подкопный муж (упрещенные требования, предложенные С. Даниловым)
    const ROLE_FUTURE_KOPNIK    = 3; // Стремлюсь стать Копным мужем
    const ROLE_FEMALE           = 4; // Женщина (жена / не замужем)
    const ROLE_STRANGER         = 5; // Чужой не член общины (наблюдатель / невидимка / аватарка / провинившийся изгой / инородец)
    static protected $roles_values = [
        self::ROLE_KOPNIK         => 'ROLE_KOPNIK',
        self::ROLE_DANILOV_KOPNIK => 'ROLE_DANILOV_KOPNIK',
        self::ROLE_FUTURE_KOPNIK  => 'ROLE_FUTURE_KOPNIK',
        self::ROLE_FEMALE         => 'ROLE_FEMALE',
        self::ROLE_STRANGER       => 'ROLE_STRANGER',
    ];

    const STATUS_NEW       = 0;
    const STATUS_PENDING   = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_DECLINE   = 3;
    static protected $status_values = [
        self::STATUS_NEW        => 'Новый',
        self::STATUS_PENDING    => 'Ожидает заверения', // Выставляется после заполнение профиля и разрешения приёма сообщений в вк
        self::STATUS_CONFIRMED  => 'Подтверждён',
        self::STATUS_DECLINE    => 'Отклонён',
    ];

    /**
     * Ссылка-приглашение на чат с заверителем
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     *
     * @deprecated надо всмпонить почему депрекедет... потому что вроде как используется...
     */
    protected ?string $assurance_chat_invite_link;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $assurance_chat_id;

    /**
     * Чат десятки, если юзер когда-либо становился "Старшиной"
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected ?string $ten_chat_invite_link;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $ten_chat_id;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default":1})
     */
    protected ?int $rank;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected ?string $locale;

    /**
     * Старшина
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="subordinates_users", cascade={"persist"}, fetch="EAGER")
     * @Gedmo\TreeParent
     */
    protected ?User $foreman;

    /**
     * Список подчинённых юзеров у старшины.
     *
     * @var User[]|Collection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="foreman", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $subordinates_users;

    /**
     * Заявка на старшину
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="foreman_requests", cascade={"persist"})
     */
    protected ?User $foreman_request;

    /**
     * Дата подачи заявки на старшину
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTimeInterface $foreman_request_date;

    /**
     * Заявки других пользователей на выбор текущего пользователя своим старшиной.
     *
     * @var User[]|Collection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="foreman_request", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $foreman_requests;

    /**
     * Cписок всеx заверенных юзеров.
     *
     * @var User[]|Collection
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="witness", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $approved_users;

    /**
     * @var UserOauth[]|Collection
     *
     * @ORM\OneToMany(targetEntity="UserOauth", mappedBy="user", cascade={"persist"}, fetch="EXTRA_LAZY")
     */
    protected Collection $oauths;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $last_login_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $confirmed_at;

    /**
     * Роль
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default":5})
     */
    protected int $kopnik_role;

    /**
     * Имя
     *
     * @ORM\Column(type="string", length=32)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected string $first_name;

    /**
     * Отчество
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected ?string $last_name;

    /**
     * Фамилия
     *
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected ?string $patronymic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $photo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected ?string $small_photo;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     * @Assert\Length(min = 4, minMessage = "Code length must be at least {{ limit }} characters long", allowEmptyString=false)
     * @Assert\Length(max = 4, minMessage = "Code length must be at least {{ limit }} characters long", allowEmptyString=false)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected ?string $passport_code;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=11, nullable=true)
     */
    protected ?float $latitude;

    /**
     * @ORM\Column(type="decimal", precision=14, scale=11, nullable=true)
     */
    protected ?float $longitude;

    /**
     * @ORM\Column(type="smallint", nullable=false, options={"unsigned"=true, "default":0})
     */
    private int $status;

    /**
     * Является заверителем?
     *
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected bool $is_witness;

    /**
     * Разрешен приём сообщений от сообщества в VK
     *
     * @ORM\Column(type="boolean", options={"default":0})
     *
     * @deprecated
     */
    protected bool $is_allow_messages_from_community;

    /**
     * Дата до которой установлена защита от флуда.
     *
     * @link https://vk.com/faq11583
     *
     * ORM\Column(type="datetime", length=4, nullable=true)
     */
    protected ?\Datetime $flood_protect_datetime; // @todo

    /**
     * @ORM\Column(type="integer", length=4, nullable=true)
     * @Assert\Length(min = 4, minMessage = "Code length must be at least {{ limit }} characters long", allowEmptyString=false)
     * @Assert\Length(max = 4, minMessage = "Code length must be at least {{ limit }} characters long", allowEmptyString=false)
     * @Assert\NotNull(message="This value is not valid.")
     */
    protected ?int $birth_year;

    /**
     * Заверитель
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="approved_users", cascade={"persist"})
     */
    protected ?User $witness;

    public function __construct()
    {
        $this->approved_users     = new ArrayCollection();
        $this->subordinates_users = new ArrayCollection();
        $this->foreman_requests   = new ArrayCollection();
        $this->first_name         = '';
        $this->created_at         = new \DateTime();
        $this->is_witness         = false;
        $this->is_allow_messages_from_community = false;
        $this->locale             = 'ru';
        $this->rank               = 1;
        $this->kopnik_role        = self::ROLE_STRANGER;
        $this->status             = self::STATUS_NEW;
    }

    public function __toString(): string
    {
        return (string) $this->getFirstName().' '. (string) $this->getLastName();
    }

    public function addClosure(UserClosure $closure)
    {
        $this->closures[] = $closure;
    }

    static public function canonicalize(string $string): ?string
    {
        if (null === $string) {
            return null;
        }

        $encoding = mb_detect_encoding($string);
        $result = $encoding
            ? mb_convert_case($string, MB_CASE_LOWER, $encoding)
            : mb_convert_case($string, MB_CASE_LOWER);

        return $result;
    }

    public function getOauthByProvider(string $provider): ?UserOauth
    {
        foreach ($this->oauths as $oauth) {
            if ($oauth->getProvider() == $provider) {
                return $oauth;
            }
        }

        throw new \Exception("Провайдер $provider не найден");
    }

    public function getVkIdentifier(): int
    {
        return (int) $this->getOauthByProvider('vkontakte')->getIdentifier();
    }

    /**
     * @deprecated
     */
    public function getAssuranceChatInviteLink(): ?string
    {
        return $this->assurance_chat_invite_link;
    }

    /**
     * @deprecated
     */
    public function setAssuranceChatInviteLink(?string $assurance_chat_invite_link): self
    {
        $this->assurance_chat_invite_link = $assurance_chat_invite_link;

        return $this;
    }

    public function getForeman(): ?User
    {
        return $this->foreman;
    }

    public function setForeman(?User $foreman): self
    {
        $this->foreman = $foreman;

        if ($foreman) {
            $this->foreman_request = null;
            $this->foreman_request_date = null;
        }

        return $this;
    }

    /**
     * @return User[]|Collection
     */
    public function getSubordinatesUsers(): Collection
    {
        return $this->subordinates_users;
    }

    /**
     * @param User[]|Collection $subordinates_users
     */
    public function setSubordinatesUsers($subordinates_users): self
    {
        $this->subordinates_users = $subordinates_users;

        return $this;
    }

    public function getWitness(): ?User
    {
        return $this->witness;
    }

    public function setWitness(?User $witness): self
    {
        $this->witness = $witness;

        return $this;
    }

    /**
     * @return User[]|Collection
     */
    public function getApprovedUsers(): Collection
    {
        return $this->approved_users;
    }

    /**
     * @param User[]|Collection $approved_users
     */
    public function setApprovedUsers($approved_users): self
    {
        $this->approved_users = $approved_users;

        return $this;
    }

    /**
     * @return UserOauth[]|Collection
     */
    public function getOauths(): Collection
    {
        return $this->oauths;
    }

    /**
     * @param UserOauth[]|Collection $oauths
     */
    public function setOauths($oauths): self
    {
        $this->oauths = $oauths;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTime
    {
        return $this->confirmed_at;
    }

    public function setConfirmedAt(?\DateTime $confirmed_at = null): self
    {
        $this->confirmed_at = $confirmed_at;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic = null): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    /**
     * @deprecated
     */
    public function getIsAllowMessagesFromCommunity(): bool
    {
        return $this->is_allow_messages_from_community;
    }

    /**
     * @deprecated
     */
    public function isAllowMessagesFromCommunity(): bool
    {
        return $this->is_allow_messages_from_community;
    }

    /**
     * @deprecated
     */
    public function setIsAllowMessagesFromCommunity(bool $is_allow_messages_from_community): self
    {
        $this->is_allow_messages_from_community = $is_allow_messages_from_community;

        return $this;
    }

    public function getBirthYear(): ?int
    {
        return $this->birth_year;
    }

    public function setBirthYear($birth_year): self
    {
        $this->birth_year = $birth_year;

        return $this;
    }

    public function getPassportCode(): ?string
    {
        return $this->passport_code;
    }

    public function setPassportCode(?string $passport_code): self
    {
        $this->passport_code = $passport_code;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
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
        /*
        if (isset(self::$roles_values[$this->role])) {
            return ['ROLE_USER', self::$roles_values[$this->role]];
        }
        */

        return ['ROLE_USER'];
    }

    /**
     * Returns the password used to authenticate the user.
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Returns the username used to authenticate the user.
     *
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

    public function getLastLoginAt(): ?\DateTime
    {
        return $this->last_login_at;
    }

    public function setLastLoginAt(?\DateTime $last_login_at = null): self
    {
        $this->last_login_at = $last_login_at;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude ? (float) $this->latitude : null;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude ? (float) $this->longitude : null;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getIsWitness(): bool
    {
        return $this->is_witness;
    }

    public function isWitness(): bool
    {
        return $this->is_witness;
    }

    public function setIsWitness(bool $is_witness): self
    {
        $this->is_witness = $is_witness;

        return $this;
    }

    static public function getStatusFormChoices(): array
    {
        return array_flip(self::$status_values);
    }

    static public function getStatusValues(): array
    {
        return self::$status_values;
    }

    static public function isStatusExist($status): bool
    {
        if (isset(self::$status_values[$status])) {
            return true;
        }

        return false;
    }

    public function getStatusAsText(): string
    {
        if (isset(self::$status_values[$this->status])) {
            return self::$status_values[$this->status];
        }

        return 'N/A';
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getSmallPhoto(): ?string
    {
        return $this->small_photo;
    }

    public function setSmallPhoto(?string $small_photo): self
    {
        $this->small_photo = $small_photo;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function hasKopnikRole(string $role): bool
    {
        return self::$roles_values[$this->kopnik_role] == $role ? true : false;
    }

    public function getKopnikRole(): int
    {
        return $this->kopnik_role;
    }

    /**
     * Для старых фикстур
     */
    public function getRole(): int
    {
        return $this->kopnik_role;
    }

    public function getKopnikRoleAsText(): ?string
    {
        if ( ! isset(self::$roles_values[$this->kopnik_role])) {
            return null;
        }

        return self::$roles_values[$this->kopnik_role];
    }

    public function setKopnikRole(int $role): self
    {
        if ( ! isset(self::$roles_values[$role])) {
            $role = self::ROLE_STRANGER;
        }

        $this->kopnik_role = $role;

        return $this;
    }

    /**
     * Для старых фикстур
     */
    public function setRole(int $role): self
    {
        return $this->setKopnikRole($role);
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getForemanRequest(): ?User
    {
        return $this->foreman_request;
    }

    public function setForemanRequest(?User $foreman_request): self
    {
        $this->foreman_request = $foreman_request;

        if ($foreman_request) {
            $this->foreman = null;
            $this->foreman_request_date = new \DateTime();
        } else {
            $this->foreman_request_date = null;
        }

        return $this;
    }

    public function getForemanRequestDate(): ?\DateTimeInterface
    {
        return $this->foreman_request_date;
    }

    public function setForemanRequestDate(?\DateTimeInterface $foreman_request_date): self
    {
        $this->foreman_request_date = $foreman_request_date;

        return $this;
    }

    /**
     * @return User[]|Collection
     */
    public function getForemanRequests(): Collection
    {
        return $this->foreman_requests;
    }

    /**
     * @param User[]|Collection $foreman_requests
     *
     * @return $this
     */
    public function setForemanRequests($foreman_requests): self
    {
        $this->foreman_requests = $foreman_requests;

        return $this;
    }

    public function getAssuranceChatId(): ?int
    {
        return $this->assurance_chat_id;
    }

    public function setAssuranceChatId(?int $assurance_chat_id): self
    {
        $this->assurance_chat_id = $assurance_chat_id;

        return $this;
    }

    public function getTenChatInviteLink(): ?string
    {
        return $this->ten_chat_invite_link;
    }

    public function setTenChatInviteLink(?string $ten_chat_invite_link): self
    {
        $this->ten_chat_invite_link = $ten_chat_invite_link;

        return $this;
    }

    public function getTenChatId(): ?int
    {
        return $this->ten_chat_id;
    }

    public function setTenChatId(?int $ten_chat_id): self
    {
        $this->ten_chat_id = $ten_chat_id;

        return $this;
    }
}
