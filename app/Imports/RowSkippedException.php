<?php

namespace App\Imports;

/**
 * Thrown from inside a DB transaction when a single import row must be
 * skipped (e.g. insufficient stock, unknown part code). The exception
 * rolls the transaction back and is caught by the caller, which then
 * records the row as skipped — leaving no partial state in the DB.
 */
class RowSkippedException extends \RuntimeException {}
