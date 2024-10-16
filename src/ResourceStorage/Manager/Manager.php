<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ResourceStorage\Manager;

use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\ResourceStorage\Collection\CollectionBuilder;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;
use ILIAS\ResourceStorage\Preloader\RepositoryPreloader;
use ILIAS\ResourceStorage\Resource\InfoResolver\StreamInfoResolver;
use ILIAS\ResourceStorage\Resource\InfoResolver\UploadInfoResolver;
use ILIAS\ResourceStorage\Resource\ResourceBuilder;
use ILIAS\ResourceStorage\Resource\StorableResource;
use ILIAS\ResourceStorage\Revision\Revision;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use ILIAS\ResourceStorage\Resource\ResourceType;

/**
 * Class StorageManager
 * @author Fabian Schmid <fabian@sr.solutions.ch>
 */
class Manager
{
    protected ResourceBuilder $resource_builder;
    protected CollectionBuilder $collection_builder;
    protected RepositoryPreloader $preloader;

    /**
     * Manager constructor.
     */
    public function __construct(
        ResourceBuilder $resource_builder,
        CollectionBuilder $collection_builder,
        RepositoryPreloader $preloader
    ) {
        $this->resource_builder = $resource_builder;
        $this->collection_builder = $collection_builder;
        $this->preloader = $preloader;
    }

    /**
     * @param bool|string $mimetype
     * @return void
     */
    protected function checkZIP(bool|string $mimetype): void
    {
        if (!in_array($mimetype, ['application/zip', 'application/x-zip-compressed'])) {
            throw new \LogicException("Cant create container resource since stream is not a ZIP");
        }
    }

    public function upload(
        UploadResult $result,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): ResourceIdentification {
        if ($result->isOK()) {
            $info_resolver = new UploadInfoResolver(
                $result,
                1,
                $stakeholder->getOwnerOfNewResources(),
                $revision_title ?? $result->getName()
            );

            $resource = $this->resource_builder->new(
                $result,
                $info_resolver
            );
            $resource->addStakeholder($stakeholder);
            $this->resource_builder->store($resource);

            return $resource->getIdentification();
        }
        throw new \LogicException("Can't handle UploadResult: " . $result->getStatus()->getMessage());
    }

    public function containerFromUpload(
        UploadResult $result,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): ResourceIdentification {
        // check if stream is a ZIP
        $this->checkZIP(mime_content_type($result->getMimeType()));

        return $this->upload($result, $stakeholder, $revision_title);
    }

    public function stream(
        FileStream $stream,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): ResourceIdentification {
        $info_resolver = new StreamInfoResolver(
            $stream,
            1,
            $stakeholder->getOwnerOfNewResources(),
            $revision_title ?? $stream->getMetadata()['uri']
        );

        $resource = $this->resource_builder->newFromStream(
            $stream,
            $info_resolver,
            true
        );
        $resource->addStakeholder($stakeholder);
        $this->resource_builder->store($resource);

        return $resource->getIdentification();
    }

    public function containerFromStream(
        FileStream $stream,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): ResourceIdentification {
        // check if stream is a ZIP
        $this->checkZIP(mime_content_type($stream->getMetadata()['uri']));

        return $this->stream($stream, $stakeholder, $revision_title);
    }

    public function find(string $identification): ?ResourceIdentification
    {
        $resource_identification = new ResourceIdentification($identification);

        if ($this->resource_builder->has($resource_identification)) {
            return $resource_identification;
        }

        return null;
    }

    // Resources

    public function getResource(ResourceIdentification $i): StorableResource
    {
        $this->preloader->preload([$i->serialize()]);
        return $this->resource_builder->get($i);
    }


    public function remove(ResourceIdentification $identification, ResourceStakeholder $stakeholder): void
    {
        $this->resource_builder->remove($this->resource_builder->get($identification), $stakeholder);
        if (!$this->resource_builder->has($identification)) {
            $this->collection_builder->notififyResourceDeletion($identification);
        }
    }

    public function clone(ResourceIdentification $identification): ResourceIdentification
    {
        $resource = $this->resource_builder->clone($this->resource_builder->get($identification));

        return $resource->getIdentification();
    }

    // Revision

    public function appendNewRevision(
        ResourceIdentification $identification,
        UploadResult $result,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): Revision {
        if ($result->isOK()) {
            if (!$this->resource_builder->has($identification)) {
                throw new \LogicException(
                    "Resource not found, can't append new version in: " . $identification->serialize()
                );
            }
            $resource = $this->resource_builder->get($identification);
            if ($resource->getType() === ResourceType::CONTAINER) {
                $this->checkZIP($result->getMimeType());
            }

            $info_resolver = new UploadInfoResolver(
                $result,
                $resource->getMaxRevision() + 1,
                $stakeholder->getOwnerOfNewResources(),
                $revision_title ?? $result->getName()
            );

            $this->resource_builder->append(
                $resource,
                $result,
                $info_resolver
            );
            $resource->addStakeholder($stakeholder);

            $this->resource_builder->store($resource);

            return $resource->getCurrentRevision();
        }
        throw new \LogicException("Can't handle UploadResult: " . $result->getStatus()->getMessage());
    }

    public function replaceWithUpload(
        ResourceIdentification $identification,
        UploadResult $result,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): Revision {
        if ($result->isOK()) {
            if (!$this->resource_builder->has($identification)) {
                throw new \LogicException(
                    "Resource not found, can't append new version in: " . $identification->serialize()
                );
            }
            $resource = $this->resource_builder->get($identification);
            if ($resource->getType() === ResourceType::CONTAINER) {
                $this->checkZIP($result->getMimeType());
            }
            $info_resolver = new UploadInfoResolver(
                $result,
                $resource->getMaxRevision() + 1,
                $stakeholder->getOwnerOfNewResources(),
                $revision_title ?? $result->getName()
            );
            $this->resource_builder->replaceWithUpload(
                $resource,
                $result,
                $info_resolver
            );
            $resource->addStakeholder($stakeholder);

            $this->resource_builder->store($resource);

            return $resource->getCurrentRevision();
        }
        throw new \LogicException("Can't handle UploadResult: " . $result->getStatus()->getMessage());
    }

    public function appendNewRevisionFromStream(
        ResourceIdentification $identification,
        FileStream $stream,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): Revision {
        if (!$this->resource_builder->has($identification)) {
            throw new \LogicException(
                "Resource not found, can't append new version in: " . $identification->serialize()
            );
        }

        $resource = $this->resource_builder->get($identification);
        if ($resource->getType() === ResourceType::CONTAINER) {
            $this->checkZIP(mime_content_type($stream->getMetadata()['uri']));
        }
        $info_resolver = new StreamInfoResolver(
            $stream,
            $resource->getMaxRevision() + 1,
            $stakeholder->getOwnerOfNewResources(),
            $revision_title ?? $stream->getMetadata()['uri']
        );

        $this->resource_builder->appendFromStream(
            $resource,
            $stream,
            $info_resolver,
            true
        );
        $resource->addStakeholder($stakeholder);

        $this->resource_builder->store($resource);

        return $resource->getCurrentRevision();
    }

    public function replaceWithStream(
        ResourceIdentification $identification,
        FileStream $stream,
        ResourceStakeholder $stakeholder,
        string $revision_title = null
    ): Revision {
        if (!$this->resource_builder->has($identification)) {
            throw new \LogicException(
                "Resource not found, can't append new version in: " . $identification->serialize()
            );
        }

        $resource = $this->resource_builder->get($identification);
        if ($resource->getType() === ResourceType::CONTAINER) {
            $this->checkZIP(mime_content_type($stream->getMetadata()['uri']));
        }
        $info_resolver = new StreamInfoResolver(
            $stream,
            $resource->getMaxRevision() + 1,
            $stakeholder->getOwnerOfNewResources(),
            $revision_title ?? $stream->getMetadata()['uri']
        );

        $this->resource_builder->replaceWithStream(
            $resource,
            $stream,
            $info_resolver,
            true
        );
        $resource->addStakeholder($stakeholder);

        $this->resource_builder->store($resource);

        return $resource->getCurrentRevision();
    }

    public function getCurrentRevision(ResourceIdentification $identification): Revision
    {
        return $this->resource_builder->get($identification)->getCurrentRevision();
    }

    public function updateRevision(Revision $revision): bool
    {
        $this->resource_builder->storeRevision($revision);

        return true;
    }

    public function rollbackRevision(ResourceIdentification $identification, int $revision_number): bool
    {
        $resource = $this->resource_builder->get($identification);
        $this->resource_builder->appendFromRevision($resource, $revision_number);
        $this->resource_builder->store($resource);

        return true;
    }

    public function removeRevision(ResourceIdentification $identification, int $revision_number): bool
    {
        $resource = $this->resource_builder->get($identification);
        $this->resource_builder->removeRevision($resource, $revision_number);
        $this->resource_builder->store($resource);

        return true;
    }
}
