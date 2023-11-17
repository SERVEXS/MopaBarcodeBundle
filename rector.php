<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/Tests',
    ]);
    $rectorConfig->sets([
        \Rector\Symfony\Set\SymfonyLevelSetList::UP_TO_SYMFONY_54,
//    \Rector\Symfony\Set\TwigLevelSetList::UP_TO_TWIG_240,
//        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_82,
                        \Rector\Doctrine\Set\DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
                \Rector\Doctrine\Set\DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
                \Rector\Symfony\Set\JMSSetList::ANNOTATIONS_TO_ATTRIBUTES,
                \Rector\Symfony\Set\SymfonySetList::SYMFONY_52_VALIDATOR_ATTRIBUTES,
                \Rector\Symfony\Set\SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
                \Rector\Symfony\Set\FOSRestSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
};
