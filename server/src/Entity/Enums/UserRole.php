<?php

namespace App\Entity\Enums;

enum UserRole: string
{
    case REPORTER = 'reporter';
    case ADMIN = 'admin';
}
