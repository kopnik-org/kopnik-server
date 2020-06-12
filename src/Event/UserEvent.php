<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserEvent extends Event
{
    // Запрос на выбор другого пользователя старшиной.
    const FOREMAN_REQUEST = 'app.user_foreman_request';

    // Одобрить заявку другого пользователя на выбор текущего пользователя старшиной.
    const FOREMAN_CONFIRM = 'app.user_foreman_confirm';

    // Отклонить заявку другого пользователя на выбор текущего пользователя старшиной.
    const FOREMAN_DECLINE = 'app.user_foreman_decline';

    // Пользователь отказался от старшины
    const FOREMAN_RESET = 'app.user_foreman_reset';

    // Старшина отказался от подчинённого
    const SUBORDINATE_RESET = 'app.user_subordinate_reset';

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
