<?php

namespace App\Entity\Enums;

enum UserState: string
{
    case ACTIVE = 'active';
    case LOCKED = 'locked';
    case DELETED = 'deleted';
}
