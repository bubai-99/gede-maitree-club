<?php

namespace App\Policies;

use App\Enums\BookCode;
use App\Models\Book;
use App\Models\LedgerEntry;
use App\Models\User;

/**
 * The three books. The treasurer sees and writes all three (books.view, books.manage);
 * the catering and nursery in-charges see their own book and write their own entries in it.
 * Reports are for whoever has reports.view. The Admin passes every check (Gate::before).
 */
class LedgerEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('books.view') || $user->can('books.catering') || $user->can('books.nursery');
    }

    public function viewBook(User $user, Book $book): bool
    {
        return $user->can('books.view') || $this->ownBook($user, $book);
    }

    public function view(User $user, LedgerEntry $entry): bool
    {
        return $this->viewBook($user, $entry->book);
    }

    public function createIn(User $user, Book $book): bool
    {
        return $user->can('books.manage') || $this->ownBook($user, $book);
    }

    public function reverse(User $user, LedgerEntry $entry): bool
    {
        if ($entry->reverses_id !== null) {
            return false;
        }

        if ($user->can('books.manage')) {
            return true;
        }

        return $this->ownBook($user, $entry->book) && $entry->recorded_by === $user->id && $entry->source_type === null;
    }

    public function viewReports(User $user): bool
    {
        return $user->can('reports.view');
    }

    private function ownBook(User $user, Book $book): bool
    {
        return match ($book->code) {
            BookCode::Catering => $user->can('books.catering'),
            BookCode::Nursery => $user->can('books.nursery'),
            BookCode::Club => false,
        };
    }
}
