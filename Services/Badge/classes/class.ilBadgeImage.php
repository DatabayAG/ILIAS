<?php

namespace ILIAS\Badge;

use ilBadge;
use ILIAS\ResourceStorage\Services;
use ilBadgeFileStakeholder;
use ILIAS\FileUpload\FileUpload;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ilGlobalTemplateInterface;

class ilBadgeImage
{
    private ?Services $resource_storage;
    private ?FileUpload $upload_service;
    private ?ilGlobalTemplateInterface $main_template;

    public function __construct(Services $resourceStorage, FileUpload $uploadService, ilGlobalTemplateInterface $main_template)
    {
        $this->resource_storage = $resourceStorage;
        $this->upload_service = $uploadService;
        $this->main_template = $main_template;
    }

    public function getImageFromBadge(ilBadge $badge) : string
    {
        $image_rid = $badge->getImageRid();
        return $this->getImageFromResourceId($badge->getId(), $image_rid);
    }

    public function getImageFromResourceId(int $badge_id, ?string $image_rid) : string
    {
        $image_src = '';

        if ($image_rid !== null) {
            $identification = $this->resource_storage->manage()->find($image_rid);
            if ($identification !== null) {
                $image_src = $this->resource_storage->consume()->src($identification)->getSrc();
            }
        } else {
            $badge = new ilBadge($badge_id);
            $image_src = $badge->getImage();
        }

        return $image_src;
    }

    public function processImageUpload(ilBadge $badge) : void
    {
        try {
            $this->upload_service->process();
            $array_result = $this->upload_service->getResults();
            $array_result = array_pop($array_result);
            $stakeholder = new ilBadgeFileStakeholder();
            $identification = $this->resource_storage->manage()->upload($array_result, $stakeholder);
            $badge->setImageRid($identification);
            $badge->update();
        } catch (IllegalStateException $e) {
            $this->main_template->setOnScreenMessage('failure', $e->getMessage(), true);
        }


    }
}