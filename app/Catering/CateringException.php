<?php

namespace App\Catering;

use App\Exceptions\FriendlyException;

/**
 * A catering order could not be moved on, or money could not be taken or refunded.
 */
class CateringException extends FriendlyException {}
