<?php
/**************************************************
 *          The SiteBuilder PHP Framework         *
 *         Copyright (c) 2021 Alpin Gencer        *
 *      Refer to LICENSE.md for a full notice     *
 **************************************************/

namespace SiteBuilder\Core\Module;

use LogicException;
use SiteBuilder\Core\FrameworkManager;
use SiteBuilder\Utils\Traits\ManagedObject;
use SiteBuilder\Utils\Traits\Runnable;
use SiteBuilder\Utils\Traits\Singleton;

final class ModuleManager {
	use ManagedObject;
	use Runnable;
	use Singleton;

	private array $modules;

	public function __construct(array $config) {
		$this->setAndAssertManager(FrameworkManager::class);
		$this->assertSingleton();
		$this->modules = array();
	}

	public function moduleInitialized(string $module_class): bool {
		return isset($this->modules[$module_class]);
	}

	public function module(string $module_class): Module {
		// Assert the given module class has been initialized: Cannot return uninitialized module
		assert(
			$this->moduleInitialized($module_class),
			new LogicException("No module of the given class '$module_class' has been initialized!")
		);

		return $this->modules[$module_class];
	}

	public function modules(): array {
		return $this->modules;
	}

	public function init(string $module_class, array $config = []): void {
		// Assert that the given class is a subclass of Module: ModuleManager only manages Modules
		assert(
			is_subclass_of($module_class, Module::class),
			new LogicException("The given class '$module_class' must be a subclass of '" . Module::class . "'!")
		);

		// Assert that the given module has not already been initialized: Cannot reinitialize Modules
		assert(
			!$module_class::initialized(),
			new LogicException("The module of the given class '$module_class' has already been initialized!")
		);

		// Init module
		$module = new $module_class();
		$this->modules[$module_class] = $module;
		$module->init($config);
	}

	public function uninit(string $module_class): void {
		// Assert that the given module is initialized: Cannot uninitialize non-existing module
		assert(
			$this->moduleInitialized($module_class),
			new LogicException("No module of the given class '$module_class' has been initialized!")
		);

		$this->modules[$module_class]->uninit();
		unset($this->modules[$module_class]);
	}

	public function uninitAll(): void {
		foreach(array_keys($this->modules) as $module_class) {
			$this->uninit($module_class);
		}
	}

	public function runEarly(): void {
		$this->assertCallerIsManager();
		$this->assertCurrentRunStage(1);

		foreach($this->modules as $module) {
			$module->runEarly();
		}
	}

	public function run(): void {
		$this->assertCallerIsManager();
		$this->assertCurrentRunStage(2);

		foreach($this->modules as $module) {
			$module->run();
		}
	}

	public function runLate(): void {
		$this->assertCallerIsManager();
		$this->assertCurrentRunStage(3);

		foreach($this->modules as $module) {
			$module->runLate();
		}
	}
}
