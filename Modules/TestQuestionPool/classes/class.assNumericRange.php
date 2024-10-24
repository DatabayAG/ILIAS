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

require_once './Modules/Test/classes/inc.AssessmentConstants.php';

/**
 * Class for numeric ranges of questions
 *
 * assNumericRange is a class for numeric ranges of questions
 *
 * @author	Helmut Schottmüller <helmut.schottmueller@mac.com>
 * @author	Maximilian Becker <mbecker@databay.de>
 *
 * @version	$Id$
 *
 * @ingroup ModulesTestQuestionPool
 *
 * @see assNumeric
 */
class assNumericRange
{
    /**
     * The lower limit of the range
     *
     * A double value containing the lower limit of the range
     *
     * @var $lowerlimit double
     */
    protected $lowerlimit;

    /**
     * The upper limit of the range
     *
     * A double value containing the upper limit of the range
     *
     * @var $upperlimit double
     */
    protected $upperlimit;

    /**
     * The points for entering a number in the correct range
     *
     * The points for entering a number in the correct range
     *
     * @var double
     */
    protected $points;

    /**
     * The order of the range in the container question
     *
     * The order of the range in the container question
     *
     * @var integer
     */
    protected $order;

    /**
     * assNumericRange constructor
     *
     * The constructor takes possible arguments an creates an instance of the assNumericRange object.
     *
     * @param double    $lowerlimit     The lower limit of the range
     * @param double    $upperlimit     The upper limit of the range
     * @param double    $points         The number of points given for the correct range
     * @param integer   $order          A nonnegative value representing a possible display or sort order
     */
    public function __construct($lowerlimit = 0.0, $upperlimit = 0.0, $points = 0.0, $order = 0)
    {
        $this->lowerlimit = $lowerlimit;
        $this->upperlimit = $upperlimit;
        $this->points = $points;
        $this->order = $order;
    }

    /**
     * Get the lower limit
     *
     * Returns the lower limit of the range
     *
     * @return double The lower limit
     *
     * @see $lowerlimit
    */
    public function getLowerLimit(): float
    {
        return $this->lowerlimit;
    }

    /**
     * Get the upper limit
     *
     * Returns the upper limit of the range
     *
     * @return double The upper limit
     *
     * @see $upperlimit
     */
    public function getUpperLimit(): float
    {
        return $this->upperlimit;
    }

    /**
     * Get the points
     *
     * Returns the points of the range
     *
     * @return double The points
     *
     * @see $points
     */
    public function getPoints(): float
    {
        return $this->points;
    }

    /**
     * Get the order of the range
     *
     * Returns the order of the range
     *
     * @return integer order
     *
     * @see $order
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Set the lower limit
     *
     * Sets the lower limit of the range
     *
     * @param double $limit The lower limit
     *
     * @see $lowerlimit
    */
    public function setLowerLimit($limit): void
    {
        $this->lowerlimit = $limit;
    }

    /**
     * Set the upper limit
     *
     * Sets the upper limit of the range
     *
     * @param double $limit The upper limit
     *
     * @see $upperlimit
     */
    public function setUpperLimit($limit): void
    {
        $this->upperlimit = $limit;
    }

    /**
     * Set the points
     *
     * Sets the points of the range
     *
     * @param double $points The points
     *
     * @see $points
     */
    public function setPoints($points): void
    {
        $this->points = $points;
    }

    /**
     * Set the order
     *
     * Sets the order of the range
     *
     * @param integer $order The order
     *
     * @see $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * Checks for a given value within the range
     *
     * Checks for a given value within the range
     *
     * @param double $value The value to check
     *
     * @return boolean TRUE if the value is in the range, FALSE otherwise
     *
     * @see $upperlimit
     * @see $lowerlimit
     */
    public function contains($value): bool
    {
        $eval = new EvalMath();
        $eval->suppress_errors = true;
        $result = $eval->e($value);
        if (($result === false) || ($result === true)) {
            return false;
        }

        if (($result >= $eval->e($this->lowerlimit)) && ($result <= $eval->e($this->upperlimit))) {
            return true;
        } else {
            return false;
        }
    }
}
