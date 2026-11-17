<?php

namespace App\Passes;

use App\Exceptions\FriendlyException;

/**
 * A gate scan could not be changed (undo window passed, someone else's scan, already undone).
 */
class GateException extends FriendlyException {}
