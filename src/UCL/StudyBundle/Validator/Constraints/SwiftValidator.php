<?php

namespace UCL\StudyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SwiftValidator extends ConstraintValidator
{
  /**
   * SWIFT-BIC validator.
   * @author ronan.guilloux
   * @license GNU GPL v3
   * @link   http://networking.mydesigntool.com/viewtopic.php?tid=301&id=31
   */
  public function validate($value, Constraint $constraint)
  {
    /* variant BIC Regex: ([a-zA-Z]{4}[a-zA-Z]{2}[a-zA-Z0-9]{2}([a-zA-Z0-9]{3})?) */
    $regexp = '/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/';

    if (!preg_match($regexp, $value, $matches)) {
      $this->context->buildViolation($constraint->message)
          ->setParameter('%string%', $value)
          ->addViolation();
    }
  }
}

?>
