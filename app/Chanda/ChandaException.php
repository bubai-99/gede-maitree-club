<?php

namespace App\Chanda;

use App\Exceptions\FriendlyException;

/**
 * A chanda receipt, sponsor payment or cash handover could not be recorded.
 */
class ChandaException extends FriendlyException {}
