<?php

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TsvectorType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TSVECTOR';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value; 
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value; 
    }

    public function getName()
    {
        return 'tsvector';
    }
}