<?php

namespace App\Models;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
}
