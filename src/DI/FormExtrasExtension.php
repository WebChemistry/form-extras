<?php declare(strict_types = 1);

namespace WebChemistry\FormExtras\DI;

use DomainException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WebChemistry\FormExtras\Factory\FormFactory;
use WebChemistry\FormExtras\Renderer\LatteFormRendererFactory;
use WebChemistry\FormExtras\Renderer\ThemeFormRendererFactory;
use WebChemistry\FormExtras\Rule\CompositeFormRuleApplier;
use WebChemistry\FormExtras\Rule\FormRuleApplierInterface;
use WebChemistry\FormExtras\Rule\SymfonyConstraintsToFormRulesInterface;
use WebChemistry\FormExtras\Rule\SymfonyValidatorFormRuleApplier;
use WebChemistry\FormExtras\Rule\SymfonyValidatorRules;
use WebChemistry\FormExtras\Theme\FormTheme;

final class FormExtrasExtension extends CompilerExtension
{

	private ServiceDefinition $ruleApplier;

	private ServiceDefinition $symfonyConstraintsToFormRules;

	private ServiceDefinition $latteFormRendererFactory;

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'rules' => Expect::structure([
				'enable' => Expect::bool(true),
			]),
			'template' => Expect::string(),
		]);
	}

	public function loadConfiguration(): void
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		if ($config->rules->enable) {
			$this->ruleApplier = new ServiceDefinition();
			$this->ruleApplier->setType(FormRuleApplierInterface::class)
				->setFactory(CompositeFormRuleApplier::class);

			$builder->addDefinition($this->prefix('ruleApplier'), $this->ruleApplier);

			if (interface_exists(ValidatorInterface::class)) {
				$this->symfonyConstraintsToFormRules = new ServiceDefinition();
				$this->symfonyConstraintsToFormRules->setType(FormRuleApplierInterface::class);
				$this->symfonyConstraintsToFormRules->setFactory(SymfonyValidatorFormRuleApplier::class);

				$builder->addDefinition($this->prefix('symfonyValidatorApplier'), $this->symfonyConstraintsToFormRules);

				$builder->addDefinition($this->prefix('symfonyValidatorRules'))
					->setType(SymfonyConstraintsToFormRulesInterface::class)
					->setFactory(SymfonyValidatorRules::class);
			}
		}

		$builder->addFactoryDefinition($this->prefix('factory'))
			->setImplement(FormFactory::class);

		$builder->addDefinition($this->prefix('latte.themeFactory'))
			->setFactory(ThemeFormRendererFactory::class);

		$builder->addDefinition(
			$this->prefix('latte.rendererFactory'),
			$this->latteFormRendererFactory = new ServiceDefinition(),
		)
			->setFactory(LatteFormRendererFactory::class);
	}

	public function beforeCompile(): void
	{
		/** @var stdClass $config */
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		if ($config->template) {
			if ($builder->findByType(FormTheme::class)) {
				throw new DomainException('Combination of theme and template is not recommended.');
			}

			foreach ($builder->findByType(FormFactory::class) as $service) {
				if (!$service instanceof FactoryDefinition) {
					continue;
				}

				$service->getResultDefinition()
					->addSetup(
						'?->setRenderer(?->create(?))',
						['@self', $this->latteFormRendererFactory, $config->template],
					);
			}
		}

		if (isset($this->ruleApplier)) {
			$appliers = [];

			foreach ($builder->findByType(FormRuleApplierInterface::class) as $definition) {
				if ($definition === $this->ruleApplier) {
					continue;
				}

				$appliers[] = $definition->setAutowired(false);
			}

			$this->ruleApplier->setArguments([$appliers]);
		}

		if (isset($this->symfonyConstraintsToFormRules)) {
			$rules = [];

			foreach ($builder->findByType(SymfonyConstraintsToFormRulesInterface::class) as $definition) {
				if ($definition === $this->symfonyConstraintsToFormRules) {
					continue;
				}

				$rules[] = $definition->setAutowired(false);
			}

			$this->symfonyConstraintsToFormRules->setArguments([$rules]);
		}
	}

}
