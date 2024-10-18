<?php

namespace ILIAS\Badge;

use ILIAS\UI\Implementation\Component\Image\Image;
use ILIAS\UI\Component\Modal\Lightbox;

class ModalBuilder
{

    private $ui_factory = null;
    private $ui_renderer = null;
    public function __construct()
    {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
    }

    public function constructModal(Image $badge_image, string $badge_title, array $properties = []) : Lightbox
    {

        $item = $this->ui_factory->item()
                                 ->standard('')
                                 ->withLeadImage($badge_image)
                                 ->withProperties($properties);

        $card = $this->ui_factory->card()
                                 ->standard($badge_title)
                                 ->withSections([$item]);

        $box = $this->ui_factory->modal()->lightboxCardPage($card);

        return $this->ui_factory->modal()->lightbox($box);
    }
}