<?php

namespace App\Enums;

enum SupportTicketType: string
{
    case Question = 'question';
    case Answer   = 'answer';
}
