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

namespace ILIAS\UI\Component\Input\Container\Form;

/**
 * This is how a factory for forms looks like.
 */
interface Factory
{
    /**
     * ---
     * description:
     *   purpose: >
     *      Standard Forms are used for creating content of sub-items or for
     *      configuring objects or services.
     *   composition: >
     *      Standard forms provide a submit-button.
     *   effect: >
     *      The users manipulates input-values and saves the form to apply the
     *      settings to the object or service or create new entities in the
     *      system.
     * rules:
     *   usage:
     *     1: Standard Forms MUST NOT be used on the same page as tables.
     *     2: Standard Forms SHOULD NOT be used on the same page as toolbars.
     *   composition:
     *     1: Each form SHOULD contain at least one section displaying a title.
     *     2: >
     *         Standard Forms MUST only be submitted by their submit-button. They MUST
     *         NOT be submitted by anything else.
     *     3: >
     *        Wording of labels of the fields the form contains and their ordering MUST
     *        be consistent with identifiers in other objects if some for is used there
     *        for a similar purpose. If you feel a wording or ordering needs to be
     *        changed, then you MUST propose it to the JF.
     *     4: >
     *        On top and bottom of a standard form there SHOULD be the “Save” button for the form.
     *     5: >
     *        In some rare exceptions the Buttons MAY be labeled differently: if “Save” is
     *        clearly a misleading since the action is more than storing
     *        the data into the database. “Send Mail” would be an example of this.
     * ---
     * @param    string $post_url
     * @param    array<mixed,\ILIAS\UI\Component\Input\Container\Form\FormInput>    $inputs
     * @return    \ILIAS\UI\Component\Input\Container\Form\Standard
     */
    public function standard(string $post_url, array $inputs): Standard;
}
