<?php

namespace App\Enums;

enum UserStatus: string
{
    case Provisional = 'provisional';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deleted = 'deleted';
}
