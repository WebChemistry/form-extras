<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WebChemistry\FormExtras\Validator\ValidatorInterface as FormValidator;

class SymfonyValidator implements FormValidator
{

	private bool $validate = true;

	public function __construct(
		private ValidatorInterface $validator,
	)
	{
	}

	public function setValidate(bool $validate): static
	{
		$this->validate = $validate;

		return $this;
	}

	/**
	 * @param mixed[]|object $values
	 */
	public function validate(Form $form, array|object $values): void
	{
		if (!$this->validate) {
			return;
		}

		if (is_array($values)) {
			return;
		}

		$errors = $this->validator->validate($values);

		if (!$errors->count()) {
			return;
		}

		/** @var ConstraintViolation $error */
		foreach ($errors as $error) {
			$property = $error->getPropertyPath();

			if (
				$property &&
				($component = $form->getComponent($property, false)) &&
				$component instanceof BaseControl
			) {
				$component->addError($error->getMessage());
			} else {
				$form->addError($error->getMessage());
			}
		}
	}

}
