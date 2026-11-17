<?php

namespace App\Payments;

use RuntimeException;

/**
 * A payment could not be started, verified or changed. The message is an English
 * translation key that can be shown to the person.
 */
class PaymentException extends RuntimeException {}
