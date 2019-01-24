<?php

use Doctrine\DBAL\Types\Type;

use Slim\Container;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;

$settings = require __DIR__ . '/src/settings.php';

if(!Type::hasType('uuid')){
    Type::addType('uuid', 'Ramsey\Uuid\Doctrine\UuidType');
}

$container = new Container($settings);

$container[EntityManager::class] = function (Container $container): EntityManager {
    $config = Setup::createAnnotationMetadataConfiguration(
        $container['settings']['doctrine']['metadata_dirs'],
        $container['settings']['doctrine']['dev_mode']
    );
    $annotationReader = new AnnotationReader();
    $config->setMetadataDriverImpl(
        new AnnotationDriver(
            $annotationReader,
            $container['settings']['doctrine']['metadata_dirs']
        )
    );

    $config->setMetadataCacheImpl(
        new FilesystemCache(
            $container['settings']['doctrine']['cache_dir']
        )
    );

    $evm = new Doctrine\Common\EventManager();
    $timestampableListener = new Gedmo\Timestampable\TimestampableListener();
    $timestampableListener->setAnnotationReader($annotationReader);
    $evm->addEventSubscriber($timestampableListener);

    return EntityManager::create(
        $container['settings']['doctrine']['connection'],
        $config,
        $evm
    );
};

return $container;

