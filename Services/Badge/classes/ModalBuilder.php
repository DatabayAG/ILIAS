<?php

namespace ILIAS\Badge;

use ILIAS\UI\Implementation\Component\Image\Image;
use ILIAS\UI\Component\Modal\Modal;

class ModalBuilder
{

    private ?\ILIAS\UI\Factory $ui_factory = null;
    private $ui_renderer = null;
    public function __construct()
    {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
    }

    public function constructModal(Image $badge_image, string $badge_title, array $properties = []) : Modal
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

    public function renderModal(Modal $modal ) {
        return $this->ui_renderer->render($modal);
    }

    public function renderShyButton(string $label, Modal $modal ) {
        $button = $this->ui_factory->button()->shy($label, $modal->getShowSignal());
        return $this->ui_renderer->render($button);
    }
}