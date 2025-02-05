<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Validator;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Form;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WebChemistry\FormExtras\Validator\ValidatorInterface as FormValidator;

class SymfonyValidator implements FormValidator
{

	private bool $validate = true;

	/** @var string|GroupSequence|array<string|GroupSequence>|null */
	private string|GroupSequence|array|null $groups = null;

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
	 * @param string|GroupSequence|array<string|GroupSequence>|null $groups
	 */
	public function setGroups(string|GroupSequence|array|null $groups = null): static
	{
		$this->groups = $groups;

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

		$errors = $this->validator->validate($values, groups: $this->groups);

		if (!$errors->count()) {
			return;
		}

		$index = $this->createControlIndex($form);

		/** @var ConstraintViolation $error */
		foreach ($errors as $error) {
			$property = $error->getPropertyPath();

			if (isset($index[$property])) {
				$index[$property]->addError($error->getMessage());
			} else {
				$form->addError($error->getMessage());
			}
		}
	}

	/**
	 * @return BaseControl[]
	 */
	private function createControlIndex(Form $form): array
	{
		$controls = [];

		/** @var BaseControl $control */
		foreach ($form->getControls() as $control) {
			$path = $control->getOption(\WebChemistry\FormExtras\Form::PropertyValidationPath) ?? $control->lookupPath(Form::class);

			$controls[$path] = $control;
		}

		return $controls;
	}

}
