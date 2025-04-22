<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordStrengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        /* @var $constraint PasswordStrength */

        if (null === $value || '' === $value) {
            return; // La contrainte NotBlank doit s'en charger
        }

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

        if (!preg_match($pattern, $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
