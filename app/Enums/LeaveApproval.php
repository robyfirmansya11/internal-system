<?php
namespace App\Enums;

enum LeaveApproval:int
{
    case REJECTED = -1;
    case WAITING_MANAGER = 0;
    case WAITING_HRD = 1;
    case APPROVED = 2;
}
