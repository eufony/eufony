<?php

namespace SiteBuilder\Core\MM;

use ErrorException;

/**
 * <p>
 * Manages the initialization and uninitialization of Modules
 * </p>
 * <p>
 * Note that ModuleManager is a Singleton class, meaning only one instance of it can be initialized
 * at a time.
 * </p>
 *
 * @author Alpin Gencer
 * @namespace SiteBuilder\Core\MM
 * @see Module
 */
class ModuleManager {
	/**
	 * Static instance field for Singleton code design in PHP
	 *
	 * @var ModuleManager
	 */
	private static $instance;
	/**
	 * An array of all initialized modules
	 *
	 * @var array
	 */
	private $modules;
	/**
	 * Wether the respective run stages were done previously
	 *
	 * @var $isRunEarly bool
	 * @var $isRun bool
	 * @var $isRunLate bool
	 */
	private $isRunEarly, $isRun, $isRunLate;

	/**
	 * Returns an instance of ModuleManager
	 *
	 * @return ModuleManager The initialized instance
	 */
	public static function init(): ModuleManager {
		// Check if static instance field is set
		// If yes, throw error: Singleton class already initialized!
		if(isset(ModuleManager::$instance)) {
			throw new ErrorException("An instance of ModuleManager has already been initialized!");
		}

		ModuleManager::$instance = new self();
		return ModuleManager::$instance;
	}

	/**
	 * Constructor for the ModuleManager.
	 * To get an instance of this class, use ModuleManager::init().
	 * The constructor also sets the superglobal '__SiteBuilder_ModuleManager' to easily get this
	 * instance.
	 *
	 * @see ModuleManager::init()
	 */
	protected function __construct() {
		$GLOBALS['__SiteBuilder_ModuleManager'] = &$this;
		$this->modules = array();
		$this->setIsRunEarly(false);
		$this->setIsRun(false);
		$this->setIsRunLate(false);
	}

	/**
	 * Runs all stages of each added module in sequential order
	 *
	 * @see Module::runEarly()
	 * @see Module::run()
	 * @see Module::runLate()
	 */
	public function runAll(): void {
		$this->runEarly();
		$this->run();
		$this->runLate();
	}

	/**
	 * Runs the first stage of each added module in order
	 *
	 * @see Module::runAll()
	 * @see Module::run()
	 * @see Module::runLate()
	 */
	public function runEarly(): void {
		// Check if the manager was run already
		// If yes, trigger warning and return: Cannot run manager multiple times
		if($this->isRunEarly) {
			trigger_error("The module manager has already been run early!", E_USER_WARNING);
			return;
		}

		$this->setIsRunEarly(true);

		foreach($this->modules as $module) {
			$module->runEarly();
		}
	}

	/**
	 * Runs the second stage of each added module in order
	 *
	 * @see Module::runAll()
	 * @see Module::runEarly()
	 * @see Module::runLate()
	 */
	public function run(): void {
		// Check if the manager was run already
		// If yes, trigger warning and return: Cannot run manager multiple times
		if($this->isRun) {
			trigger_error("The module manager has already been run!", E_USER_WARNING);
			return;
		}

		$this->setIsRun(true);

		foreach($this->modules as $module) {
			$module->run();
		}
	}

	/**
	 * Runs the third stage of each added module in order
	 *
	 * @see Module::runAll()
	 * @see Module::runEarly()
	 * @see Module::run()
	 */
	public function runLate(): void {
		// Check if the manager was run already
		// If yes, trigger warning and return: Cannot run manager multiple times
		if($this->isRunLate) {
			trigger_error("The module manager has already been run late!", E_USER_WARNING);
			return;
		}

		$this->setIsRunLate(true);

		foreach($this->modules as $module) {
			$module->runLate();
		}
	}

	/**
	 * Check if a module of a given class has been initialized
	 *
	 * @param string $moduleClass The module class to search for
	 * @return bool The boolean result
	 */
	public function isModuleInitialized(string $moduleClass): bool {
		return isset($this->modules[$moduleClass]);
	}

	/**
	 * Gets an initialized module by its class name
	 *
	 * @param string $moduleClass The module class to search for
	 * @return Module The found module
	 */
	public function getModuleByClass(string $moduleClass): Module {
		// Check if module has been initialized
		// If no, throw error: The module must be initialized first
		if(!$this->isModuleInitialized($moduleClass)) {
			throw new ErrorException("No module of the given class '$moduleClass' has been initialized!");
		}

		return $this->modules[$moduleClass];
	}

	/**
	 * Gets all initailized modules
	 *
	 * @return array An array of all active modules
	 */
	public function getAllModules(): array {
		return $this->modules;
	}

	/**
	 * Initialize a module of a given class
	 *
	 * @param string $moduleClass The module class to initialize
	 * @param array $config The configuration parameters to pass to the module
	 * @return Module The initiated module instance
	 */
	public function initModule(string $moduleClass, array $config = []): Module {
		// Check if module has already been initialized
		// If yes, throw error: Only one module of each class can be initialized at a time
		if($this->isModuleInitialized($moduleClass)) {
			throw new ErrorException("A module of the given class '$moduleClass' has already been initialized!");
		}

		$module = new $moduleClass($config);
		$this->modules[$moduleClass] = $module;
		return $module;
	}

	/**
	 * Uninitailize a module of a given class
	 *
	 * @param string $moduleClass The module class to uninitailize
	 */
	public function uninitModule(string $moduleClass): void {
		// Check if module has been initialized
		// If no, throw error: Cannot uninitiate a module that hasn't been initiated
		if(!$this->isModuleInitialized($moduleClass)) {
			throw new ErrorException("No module of the given class '$moduleClass' has been initialized!");
		}

		unset($this->modules[$moduleClass]);
	}

	/**
	 * Uninitialize all active modules
	 */
	public function uninitAllModules(): void {
		foreach(array_keys($this->modules) as $moduleClass) {
			$this->uninitModule($moduleClass);
		}
	}

	/**
	 * Getter for wether the first run stage was completed
	 *
	 * @return bool
	 * @see ModuleManager::$isRunEarly
	 */
	public function isRunEarly(): bool {
		return $this->isRunEarly;
	}

	/**
	 * Setter for wether the first run stage was completed
	 *
	 * @param bool $isRunEarly
	 * @see ModuleManager::$isRunEarly
	 */
	private function setIsRunEarly(bool $isRunEarly): void {
		$this->isRunEarly = $isRunEarly;
	}

	/**
	 * Getter for wether the second run stage was completed
	 *
	 * @return bool
	 * @see ModuleManager::$isRun
	 */
	public function isRun(): bool {
		return $this->isRun;
	}

	/**
	 * Setter for wether the second run stage was completed
	 *
	 * @param bool $isRunEarly
	 * @see ModuleManager::$isRun
	 */
	private function setIsRun(bool $isRun): void {
		$this->isRun = $isRun;
	}

	/**
	 * Getter for wether the third run stage was completed
	 *
	 * @return bool
	 * @see ModuleManager::$isRunLate
	 */
	public function isRunLate(): bool {
		return $this->isRunLate;
	}

	/**
	 * Setter for wether the third run stage was completed
	 *
	 * @param bool $isRunEarly
	 * @see ModuleManager::$isRunLate
	 */
	private function setIsRunLate(bool $isRunLate): void {
		$this->isRunLate = $isRunLate;
	}

}
