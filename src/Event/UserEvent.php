<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserEvent extends Event
{
    // Запрос на выбор другого пользователя старшиной.
    const FOREMAN_REQUEST = 'app.user_foreman_request';

    // Одобрить заявку другого пользователя на выбор текущего пользователя старшиной. До того как была смена старшины.
    const FOREMAN_CONFIRM_BEFORE_CHANGE = 'app.user_foreman_confirm_before_change';

    // Одобрить заявку другого пользователя на выбор текущего пользователя старшиной. После того как произошло назначение старшины.
    const FOREMAN_CONFIRM_AFTER_CHANGE = 'app.user_foreman_confirm_after_change';

    // Отклонить заявку другого пользователя на выбор текущего пользователя старшиной.
    const FOREMAN_DECLINE = 'app.user_foreman_decline';

    // Пользователь отказался от старшины
    const FOREMAN_RESET = 'app.user_foreman_reset';

    // Старшина отказался от подчинённого
    const SUBORDINATE_RESET = 'app.user_subordinate_reset';

    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
