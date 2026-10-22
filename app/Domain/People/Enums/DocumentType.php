<?php

namespace App\Domain\People\Enums;

/** Student documents kept in FileStore; each is its own collection. The photo is separate. */
enum DocumentType: string
{
    case BirthCertificate = 'birth_certificate';
    case FamilyBook = 'family_book';
    case Other = 'other_document';
}
