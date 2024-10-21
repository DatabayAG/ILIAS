<?php

namespace ILIAS\Badge;

use ILIAS\UI\Implementation\Component\Image\Image;
use ILIAS\UI\Component\Modal\Modal;
use ilWACSignedPath;
use ilBadgeAssignment;
use ilLanguage;
use ilDateTime;
use ilDatePresentation;

class ModalBuilder
{

    private ?\ILIAS\UI\Factory $ui_factory = null;
    private ?\ILIAS\UI\Renderer $ui_renderer = null;

    protected ?ilBadgeAssignment $assignment = null;

    protected ilLanguage $lng;
    public function __construct(ilBadgeAssignment $assignment = null)
    {
        global $DIC;
        $this->ui_factory = $DIC->ui()->factory();
        $this->ui_renderer = $DIC->ui()->renderer();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule("badge");

        if ($assignment) {
            $this->assignment = $assignment;
        }
    }

    public function constructModal(Image $badge_image, string $badge_title, array $properties = []) : Modal
    {
        $modal_content[] = $badge_image;

        if ($this->assignment) {
            $properties[$this->lng->txt("badge_issued_on")] = ilDatePresentation::formatDate(
                new ilDateTime($this->assignment->getTimestamp(), IL_CAL_UNIX)
            );
        }

        $box = $this->ui_factory->listing()->descriptive($properties);

        $modal_content[] = $box;
        return $this->ui_factory->modal()->roundtrip($badge_title, $modal_content);
    }

    public function renderModal(Modal $modal ) : string
    {
        return $this->ui_renderer->render($modal);
    }

    public function renderShyButton(string $label, Modal $modal ) : string
    {
        $button = $this->ui_factory->button()->shy($label, $modal->getShowSignal());
        return $this->ui_renderer->render($button);
    }
}