<?php

namespace UCL\StudyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Swift extends Constraint
{
    public $message = 'This is not a valid SWIFT code.';
}

?>
