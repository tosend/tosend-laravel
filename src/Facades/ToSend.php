<?php

namespace ToSend\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use ToSend\Laravel\Contracts\ToSendClient;
use ToSend\Laravel\Data\EmailResponse;
use ToSend\Laravel\Data\BatchResponse;
use ToSend\Laravel\Data\AccountInfo;

/**
 * @method static EmailResponse send(array $params)
 * @method static BatchResponse batch(array $emails)
 * @method static AccountInfo getAccountInfo()
 *
 * @see \ToSend\Laravel\ToSend
 */
class ToSend extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return ToSendClient::class;
    }
}
