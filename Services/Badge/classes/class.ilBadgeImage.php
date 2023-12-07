<?php

namespace ILIAS\Badge;

use ilBadge;
use ILIAS\ResourceStorage\Services;

class ilBadgeImage
{
    private ?Services $resource_storage = null;

    public function __construct(Services $resourceStorage)
    {
        $this->resource_storage = $resourceStorage;
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
}