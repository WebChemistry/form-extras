<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\Rule;

use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\Form;
use Nette\Forms\Rule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Negative;
use Symfony\Component\Validator\Constraints\NegativeOrZero;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Url;

final class SymfonyValidatorRules implements SymfonyNetteRuleInterface
{

	public function supports(BaseControl $control, Constraint $constraint, array $context = []): bool
	{
		return str_starts_with($constraint::class, 'Symfony\\Component\\Validator\\Constraints\\');
	}

	public function apply(BaseControl $control, Constraint $constraint, array $context = []): void
	{
		if ($constraint instanceof NotBlank) {
			if ($constraint->allowNull && $control instanceof TextBase) {
				$control->setNullable();
			}

			if ($constraint->allowNull || $this->hasRule($control, Form::FILLED)) {
				return;
			}

			$control->setRequired($constraint->message);

		} else if ($constraint instanceof NotNull) {
			if ($control instanceof TextBase) {
				$control->setNullable(false);
			}

		} else if ($constraint instanceof Url) {
			if ($this->hasRule($control, Form::URL)) {
				return;
			}

			$control->addRule(Form::URL, $this->replaceMessageTemplate($constraint->message));

		} else if ($constraint instanceof Length) {
			if ($this->hasRule($control, Form::LENGTH, Form::MAX_LENGTH, Form::MIN_LENGTH)) {
				return;
			}

			if ($constraint->min !== null && $constraint->max !== null && $constraint->min === $constraint->max) {
				$control->addRule(
					Form::LENGTH,
					$this->replaceMessageTemplate(
						$constraint->exactMessage,
						['{{ limit }}' => $constraint->min]
					),
					$constraint->min
				);

				return;

			}

			if ($constraint->min !== null) {
				$control->addRule(
					Form::MIN_LENGTH,
					$this->replaceMessageTemplate(
						$constraint->minMessage,
						['{{ limit }}' => $constraint->min]
					),
					$constraint->min,
				);
			}

			if ($constraint->max !== null) {
				$control->addRule(
					Form::MAX_LENGTH,
					$this->replaceMessageTemplate(
						$constraint->maxMessage,
						['{{ limit }}' => $constraint->max]
					),
					$constraint->max,
				);
			}

		} else if ($constraint instanceof Email) {
			if ($this->hasRule($control, Form::EMAIL)) {
				return;
			}

			$control->addRule(Form::EMAIL, $this->replaceMessageTemplate($constraint->message));

		} else if ($constraint instanceof Regex) {
			$regex = $this->regexToJsRegex($constraint->pattern);
			if ($regex === null) {
				return;
			}

			$control->addRule(Form::PATTERN, $this->replaceMessageTemplate($constraint->message));
		} else if ($constraint instanceof EqualTo) {
			$control->addRule(Form::EQUAL, $this->replaceMessageTemplate($constraint->message, [
				'{{ compared_value }}' => $constraint->value,
			]), $constraint->value);

		} else if ($constraint instanceof NotEqualTo) {
			$control->addRule(Form::NOT_EQUAL, $this->replaceMessageTemplate($constraint->message, [
				'{{ compared_value }}' => $constraint->value,
			]), $constraint->value);

		} else if ($constraint instanceof LessThan || $constraint instanceof LessThanOrEqual) {
			$equal = $constraint instanceof LessThanOrEqual;
			$control->addRule(Form::MAX, $this, $this->replaceMessageTemplate($constraint->message, [
				'{{ compared_value }}' => $constraint->value,
			]), $equal ? $constraint->value : $constraint->value - 1);

		} else if ($constraint instanceof GreaterThan || $constraint instanceof GreaterThanOrEqual) {
			$equal = $constraint instanceof GreaterThanOrEqual;
			$control->addRule(Form::MIN, $this, $this->replaceMessageTemplate($constraint->message, [
				'{{ compared_value }}' => $constraint->value,
			]), $equal ? $constraint->value : $constraint->value + 1);

		} else if ($constraint instanceof Range) {
			if ($constraint->min !== null) {
				$control->addRule(Form::MIN, $this->replaceMessageTemplate($constraint->minMessage, [
					'{{ compared_value }}' => $constraint->min,
				]), $constraint->min);
			}

			if ($constraint->max !== null) {
				$control->addRule(Form::MAX, $this->replaceMessageTemplate($constraint->maxMessage, [
					'{{ compared_value }}' => $constraint->max,
				]), $constraint->max);
			}

		} else if ($constraint instanceof Positive) {
			$control->addRule(Form::MIN, $this->replaceMessageTemplate($constraint->message), 1);

		} else if ($constraint instanceof PositiveOrZero) {
			$control->addRule(Form::MIN, $this->replaceMessageTemplate($constraint->message), 0);

		} else if ($constraint instanceof Negative) {
			$control->addRule(Form::MAX, $this->replaceMessageTemplate($constraint->message), -1);

		} else if ($constraint instanceof NegativeOrZero) {
			$control->addRule(Form::MAX, $this->replaceMessageTemplate($constraint->message), 0);

		}
	}

	private function regexToJsRegex(string $regex): ?string
	{
		$length = strlen($regex);
		if ($length === 0) {
			return null;
		}

		$delimiter = $regex[0];

		$pos = strrpos($regex, $delimiter);
		if (!$pos) { // false and 0 position
			return null;
		}

		if ($pos !== $length - 1) {
			return null;
		}

		return substr($regex, 1, -1);
	}

	private function hasRule(BaseControl $control, string ... $validators): bool
	{
		/** @var Rule $rule */
		foreach ($control->getRules() as $rule) {
			foreach ($validators as $validator) {
				if ($rule->validator === $validator) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param string $template
	 * @param array<string, string|int|float> $parameters
	 * @return string
	 */
	private function replaceMessageTemplate(string $template, array $parameters = []): string
	{
		$parameters['{{ value }}'] = '%value';

		$template = strtr($template, $parameters);

		return ($pos = strpos($template, '|')) !== false ? substr($template, 0, $pos) : $template;
	}

}
