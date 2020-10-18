<?php

namespace SiteBuilder;

use SplObjectStorage;

/**
 * The Family class determines a set of Components a Page must and must not have
 * to be accepted and processed by a System.
 *
 * @author Alpin Gencer
 * @namespace SiteBuilder
 * @see Page
 * @see Component
 * @see System
 */
class Family {
	/**
	 * The arrays specifying the ruleset of this Family
	 *
	 * @var array $all
	 * @var array $one
	 * @var array $none
	 */
	private $all, $one, $none;

	/**
	 * Return an instance of Family
	 *
	 * @return self The instantiated instance
	 * @see Family::__construct()
	 */
	public static function newInstance(): self {
		return new self();
	}

	/**
	 * Constructor for Family.
	 * To get an instance of this class with chainable functions,
	 * use Family::newInstance().
	 *
	 * @see Family::newInstance()
	 */
	public function __construct() {
		$this->all = array();
		$this->one = array();
		$this->none = array();
	}

	/**
	 * Require that a page has all the given component classes
	 *
	 * @param string ...$classes The class names to require
	 * @return self Returns itself to chain other functions
	 * @see Family::requireOne(string ...$classes)
	 * @see Family::requireNone(string ...$classes)
	 * @see Family::matches(SplObjectStorage $components)
	 */
	public function requireAll(string ...$classes): self {
		$this->all = array_merge($this->all, $classes);
		return $this;
	}

	/**
	 * Require that a page has at least one of the given component classes
	 *
	 * @param string ...$classes The class names, of which at least one is required
	 * @return self Returns itself to chain other functions
	 * @see Family::requireAll(string ...$classes)
	 * @see Family::requireNone(string ...$classes)
	 * @see Family::matches(SplObjectStorage $components)
	 */
	public function requireOne(string ...$classes): self {
		$this->one = array_merge($this->one, $classes);
		return $this;
	}

	/**
	 * Require that a page has none of the given component classes
	 *
	 * @param string ...$classes The class names to exclude
	 * @return self Returns itself to chain other functions
	 * @see Family::requireAll(string ...$classes)
	 * @see Family::requireOne(string ...$classes)
	 * @see Family::matches(SplObjectStorage $components)
	 */
	public function requireNone(string ...$classes): self {
		$this->none = array_merge($this->none, $classes);
		return $this;
	}

	/**
	 * Check if the given components matches the ruleset of this Family,
	 * as specified previously by the requireAll(), requireOne(), and requireNone() functions.
	 * Note the component can also be a subclass of a specified class.
	 *
	 * @param SplObjectStorage $components The components to check
	 * @return bool The boolean result
	 * @see Family::requireAll(string ...$classes)
	 * @see Family::requireOne(string ...$classes)
	 * @see Family::requireNone(string ...$classes)
	 */
	public function matches(SplObjectStorage $components): bool {
		// Check if $components contains at least one of $none (if yes, fail)
		foreach($this->none as $exclude) {
			foreach($components as $component) {
				if(get_class($component) === $exclude || is_subclass_of($component, $exclude)) {
					return false;
				}
			}
		}

		// Check if $components contains at least one of $one (if no, fail)
		$includesOne = false;

		foreach($this->one as $include) {
			foreach($components as $component) {
				if(get_class($component) === $include || is_subclass_of($component, $include)) {
					$includesOne = true;
					break 2;
				}
			}
		}

		if(!$includesOne && sizeof($this->one) > 0) {
			return false;
		}

		// Check if $components contains at least one of each in $all (if no, fail)
		foreach($this->all as $require) {
			$containsThis = false;

			foreach($components as $component) {
				if(get_class($component) === $require || is_subclass_of($component, $require)) {
					$containsThis = true;
					break 1;
				}
			}

			if(!$containsThis) {
				return false;
			}
		}

		// If all three checks pass, return true
		return true;
	}

}