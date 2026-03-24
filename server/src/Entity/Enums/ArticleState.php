<?php

namespace App\Entity\Enums;

enum ArticleState: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}