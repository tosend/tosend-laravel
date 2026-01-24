<?php

namespace ToSend\Laravel\Contracts;

use ToSend\Laravel\Data\EmailResponse;
use ToSend\Laravel\Data\BatchResponse;
use ToSend\Laravel\Data\AccountInfo;

interface ToSendClient
{
    /**
     * Send a single email.
     */
    public function send(array $params): EmailResponse;

    /**
     * Send multiple emails in a batch.
     */
    public function batch(array $emails): BatchResponse;

    /**
     * Get account information.
     */
    public function getAccountInfo(): AccountInfo;
}
