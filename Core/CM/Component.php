<?php

namespace SiteBuilder\Core\CM;

use ErrorException;

/**
 * <p>
 * The Component class is used to output HTML to the page's body.
 * In order to use components, add them to the ContentManager using ContentManager::addComponent().
 * </p>
 * <p>
 * Components must have three basic functions:
 * </p>
 * <ol>
 * <li>Generating HTML string to add to the page body</li>
 * <li>Getting a list of CSS and JS dependencies to add to the page head</li>
 * <li>Setting and using HTML IDs and classes. Please note that any class extending this abstract
 * class is responsible for generating the corresponding HTML.</li>
 * </ol>
 *
 * @author Alpin Gencer
 * @namespace SiteBuilder\Core\CM
 * @see ContentManager
 * @see Dependency
 */
abstract class Component {
	/**
	 * The string that should be generated in the 'id' HTML attribute
	 *
	 * @var string
	 */
	private $htmlID;
	/**
	 * The string that should be generated in the 'class' HTML attribute
	 *
	 * @var string
	 */
	private $htmlClasses;

	/**
	 * Constructor for the Component.
	 * Please note that when extending this abstract class, it is convention to set the visibility
	 * of the constructor to private and create a public static init() method. See
	 * StaticHTMLComponent for an example.
	 *
	 * @see StaticHTMLComponent
	 */
	protected function __construct() {
		$this->clearHtmlID();
		$this->clearHtmlClasses();
	}

	/**
	 * Returns an array of CSS and JS dependencies to automatically get added to the page's head
	 *
	 * @return array
	 * @see Dependency
	 */
	public abstract function getDependencies(): array;

	/**
	 * Generates the HTML string to be added to the page's body
	 *
	 * @return string
	 */
	public abstract function getContent(): string;

	/**
	 * Getter for the HTML ID
	 *
	 * @return string
	 */
	public final function getHtmlID(): string {
		return $this->htmlID;
	}

	/**
	 * Setter for the HTML ID.
	 * Please note that it is pointless to set the ID of a StaticHTMLComponent
	 *
	 * @param string $htmlID
	 * @return self Returns itself for chaining other functions
	 */
	public final function setHtmlID(string $htmlID): self {
		// Check if this is a StaticHTMLComponent
		// If yes, throw error: ID of StaticHTMLComponent must be defined manually
		if($this instanceof StaticHTMLComponent) {
			throw new ErrorException("Cannot set HTML ID of a StaticHTMLComponent!");
		}

		$this->htmlID = $htmlID;
		return $this;
	}

	/**
	 * Clears the HTML ID
	 *
	 * @return self Returns itself for chaining other functions
	 */
	public final function clearHtmlID(): self {
		$this->setHtmlID('');
		return $this;
	}

	/**
	 * Getter for the HTML classes
	 *
	 * @return string
	 */
	public final function getHtmlClasses(): string {
		return $this->htmlClasses;
	}

	/**
	 * Setter for the HTML classes.
	 * Please note that it is pointless to set the HTML classes of a StaticHTMLComponent
	 *
	 * @param string $htmlClasses
	 * @return self Returns itself for chaining other functions
	 */
	public final function setHtmlClasses(string $htmlClasses): self {
		// Check if this is a StaticHTMLComponent
		// If yes, throw error: Classes of StaticHTMLComponent must be defined manually
		if($this instanceof StaticHTMLComponent) {
			throw new ErrorException("Cannot set HTML classes of a StaticHTMLComponent!");
		}

		$this->htmlClasses = $htmlClasses;
		return $this;
	}

	/**
	 * Appends HTML classes to the previous classes
	 *
	 * @param string $htmlClasses
	 * @return self Returns itself for chaining other functions
	 */
	public final function addHTMLClasses(string $htmlClasses): self {
		$htmlClasses = trim($htmlClasses);

		if(empty($this->htmlClasses)) {
			$this->setHTMLClasses($htmlClasses);
		} else {
			$this->setHTMLClasses($this->htmlClasses . ' ' . $htmlClasses);
		}

		return $this;
	}

	/**
	 * Clears the HTML classes
	 *
	 * @return self Returns itself for chaining other functions
	 */
	public final function clearHtmlClasses(): self {
		$this->setHtmlClasses('');
		return $this;
	}

}

