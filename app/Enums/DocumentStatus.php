<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case PendingApproval = 'pending_approval';
    case Ready = 'ready';
    case Failed = 'failed';
}
