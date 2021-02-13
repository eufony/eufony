<?php
/**************************************************
 *            The Eufony PHP Framework            *
 *         Copyright (c) 2021 Alpin Gencer        *
 *      Refer to LICENSE.md for a full notice     *
 **************************************************/

namespace Eufony\Core\Content;

use Eufony\Utils\Classes\Collections\AttributeCollection;
use Eufony\Utils\Classes\File;
use Stringable;

abstract class AssetDependency implements Stringable {
	private string $source;
	private AttributeCollection $attributes;

	public final static function removeDuplicates(array &$dependencies): void {
		$added_dependencies = array();
		$added_dependency_sources = array();

		foreach($dependencies as $dependency) {
			$dependency_class = $dependency::class;
			$added_dependency_sources[$dependency_class] ??= array();

			if(in_array($dependency->source(), $added_dependency_sources[$dependency_class], true)) {
				continue;
			}

			array_push($added_dependency_sources[$dependency_class], $dependency->source());
			array_push($added_dependencies, $dependency);
		}

		$dependencies = $added_dependencies;
	}

	public final static function path(string $source): string {
		// Check if source starts with '/'
		// If yes, return unedited string: Absolute path given
		if(File::isAbsolutePath($source)) {
			return $source;
		}

		if(File::exists("/public/assets/$source")) {
			// File in assets folder
			return "/assets/$source";
		} else {
			// File elsewhere
			return $source;
		}
	}

	public function __construct(string $source) {
		ContentManager::instance()->dependencies()->add($this);
		$this->source = AssetDependency::path($source);
		$this->attributes = new AttributeCollection();
	}

	public final function __toString(): string {
		return $this->html();
	}

	public abstract function html(): string;

	public final function source(): string {
		return $this->source;
	}

	public final function attributes(): AttributeCollection {
		return $this->attributes;
	}

}
