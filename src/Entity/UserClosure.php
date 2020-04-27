<?php

declare(strict_types=1);

namespace App\Entity;

use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("users_closure")
 */
class UserClosure extends AbstractClosure
{
}
