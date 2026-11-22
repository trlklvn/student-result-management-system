<?php
declare(strict_types=1);

namespace App\Services;

/** Thrown when user input breaks a business rule; the message is safe to show the user. */
final class ValidationException extends \RuntimeException
{
}
