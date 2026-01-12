<?php

namespace AratKruglik\Monobank\Enums;

enum InvoiceStatus: string
{
    case CREATED = 'created';
    case PROCESSING = 'processing';
    case HOLD = 'hold';
    case SUCCESS = 'success';
    case FAILURE = 'failure';
    case REVERSED = 'reversed';
    case EXPIRED = 'expired';
}
