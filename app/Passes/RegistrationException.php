<?php

namespace App\Passes;

use App\Exceptions\FriendlyException;

/**
 * A family could not register, change or cancel a registration (closed, full, over the limit).
 */
class RegistrationException extends FriendlyException {}
