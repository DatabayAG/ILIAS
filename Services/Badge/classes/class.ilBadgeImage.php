<?php

namespace ILIAS\Badge;

use ilBadge;
use ILIAS\ResourceStorage\Services;
use ilBadgeFileStakeholder;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Exception\IllegalStateException;

class ilBadgeImage
{
    private ?Services $resource_storage = null;
    private ?FileUpload $upload_service = null;

    public function __construct(Services $resourceStorage, FileUpload $uploadService)
    {
        $this->resource_storage = $resourceStorage;
        $this->upload_service = $uploadService;
    }

    public function getImageFromBadge(ilBadge $badge) : string
    {
        $image_rid = $badge->getImageRid();
        return $this->getImageFromResourceId($badge->getId(), $image_rid);
    }

    public function getImageFromResourceId(int $badge_id, ?string $image_rid) : string
    {
        if ($image_rid !== null) {
            $identification = $this->resource_storage->manage()->find($image_rid);
            $image_src = $this->resource_storage->consume()->src($identification)->getSrc();
        } else {
            $badge = new ilBadge($badge_id);
            $image_src = $badge->getImage();
        }
        return $image_src;
    }

    /**
     * @param ilBadge $badge
     * @return void
     * @throws IllegalStateException
     */
    public function processImageUpload(ilBadge $badge) : void
    {
        $this->upload_service->process();
        $array_result = $this->upload_service->getResults();
        $array_result = array_pop($array_result);
        $stakeholder = new ilBadgeFileStakeholder();
        $identification = $this->resource_storage->manage()->upload($array_result, $stakeholder);
        $badge->setImageRid($identification);
        $badge->update();
    }
}