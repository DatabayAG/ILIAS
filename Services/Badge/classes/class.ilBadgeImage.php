<?php

namespace ILIAS\Badge;

use ilBadge;

class ilBadgeImage
{

    private $dic;
    public function __construct($DIC)
    {
        $this->dic = $DIC;
    }

    /**
     * @param int    $badge_id
     * @param string $image_rid
     * @return string
     */
    public function getImageFromResourceId(int $badge_id, string $image_rid) : string
    {
        if ($image_rid !== null) {
            $identification = $this->dic['resource_storage']->manage()->find($image_rid);
            $image_src = $this->dic['resource_storage']->consume()->src($identification)->getSrc();
        } else {
            $badge = new ilBadge($badge_id);
            $image_src = $this->dic['resource_storage']->consume()->src($badge->getImage());
        }
        return $image_src;
    }

    public function getImageFromBadge(ilBadge $badge) : string
    {
        $image_rid = $badge->getImageRid();
        return $this->getImageFromResourceId($badge->getId(), $image_rid);
    }
}