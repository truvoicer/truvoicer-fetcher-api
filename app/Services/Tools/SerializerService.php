<?php

namespace App\Services\Tools;

use App\Normalizer\EntityNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

class SerializerService
{

    private $classMetadataFactory;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
    }

    public function scraperEntityToArray($entity, array $groups = ['job'])
    {
        $metadataAwareNameConverter = new MetadataAwareNameConverter($this->classMetadataFactory);
        $normalizer = new ObjectNormalizer($this->classMetadataFactory, $metadataAwareNameConverter, null,
            null, null, null, $this->getScraperContext());
        $serializer = new Serializer([$normalizer]);
        return $serializer->normalize($entity, null,
            [
                "groups" => $groups
            ]);
    }

    public function entityToArray($entity, array $groups = ['main'])
    {
        $normalizer = new ObjectNormalizer($this->classMetadataFactory, new CamelCaseToSnakeCaseNameConverter(), null,
            null, null, null, $this->getDefaultContext());
        $serializer = new Serializer([$normalizer]);
        return $serializer->normalize($entity, null,
            [
                "groups" => $groups
            ]);
    }

    public function entityArrayToArray(array $entityArray, array $groups = ['main'])
    {
        return array_map(function ($item) use ($groups) {
            return $this->entityToArray($item, $groups);
        }, $entityArray);
    }

    public function entityToXml($entity, array $groups)
    {
        $normalizer = new EntityNormalizer(
            $this->entityManager,
            $this->classMetadataFactory,
            null,
            null,
            null,
            null,
            null,
            [
                ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                    return $object;
                },
            ]
        );
        $serializer = new Serializer([$normalizer], [new XmlEncoder()]);

        return $serializer->serialize($entity, "xml", ["groups" => $groups]);
    }

    public function xmlArrayToEntities(string $xmlContent, string $class)
    {
        $normalizer = new EntityNormalizer(
            $this->entityManager,
            $this->classMetadataFactory,
            null,
            null,
            new ReflectionExtractor(),
            null,
            null,
            []
        );
        $serializer = new Serializer([$normalizer, new ArrayDenormalizer()], [new XmlEncoder()]);
        return $serializer->deserialize($xmlContent, $class."[]", 'xml', [
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        ]);
    }

    private function getDefaultContext()
    {
        $dateCallback = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
            return $innerObject instanceof \DateTime ? $innerObject->format(\DateTime::ISO8601) : '';
        };
        return [
            ObjectNormalizer::CALLBACKS => [
                'date_added' => $dateCallback,
                'date_updated' => $dateCallback,
                'date_created' => $dateCallback,
                'dateUpdated' => $dateCallback,
                'dateCreated' => $dateCallback,
                'expiresAt' => $dateCallback,
                'start_date' => $dateCallback,
                'end_date' => $dateCallback,
                'startDate' => $dateCallback,
                'endDate' => $dateCallback,
            ],
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return false;
            },
        ];
    }

    private function getScraperContext()
    {
        $responseKeysCallback = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
            $responseKeysArray = [];
            foreach ($outerObject->getScraper()->getScraperResponseKeys() as $responseKey) {
                array_push($responseKeysArray, [
                   "name" => $responseKey->getServiceResponseKey()->getKeyName(),
                   "selector" => $responseKey->getResponseKeySelector()
                ]);
            }
            return $responseKeysArray;
        };
        $dateCallback = function ($innerObject, $outerObject, string $attributeName, string $format = null, array $context = []) {
            return $innerObject instanceof \DateTime ? $innerObject->format(\DateTime::ISO8601) : '';
        };
        return [
            ObjectNormalizer::CALLBACKS => [
                'date_added' => $dateCallback,
                'date_updated' => $dateCallback,
                'date_created' => $dateCallback,
                'dateUpdated' => $dateCallback,
                'dateCreated' => $dateCallback,
                'expiresAt' => $dateCallback,
                'start_date' => $dateCallback,
                'end_date' => $dateCallback,
                'startDate' => $dateCallback,
                'endDate' => $dateCallback,
                'singleItemDataConfig' => $responseKeysCallback,
            ],
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object;
            },
        ];
    }
}
