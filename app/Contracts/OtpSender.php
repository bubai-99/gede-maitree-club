<?php

namespace App\Contracts;

/**
 * Sends a one-time sign-in code to a mobile number.
 *
 * Only a log driver exists today: sending real SMS in India needs a DLT-registered
 * sender and template, which the club has not set up yet.
 */
interface OtpSender
{
    public function send(string $phone, string $code): void;
}
