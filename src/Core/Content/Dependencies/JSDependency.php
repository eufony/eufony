<?php
/**************************************************
 *          The SiteBuilder PHP Framework         *
 *         Copyright (c) 2021 Alpin Gencer        *
 *      Refer to LICENSE.md for a full notice     *
 **************************************************/

namespace SiteBuilder\Core\Content\Dependencies;

use SiteBuilder\Core\Content\AssetDependency;

final class JSDependency extends AssetDependency {

	public function __construct(string $source, bool $defer = false) {
		parent::__construct($source);

		if($defer) {
			$this->attribute('defer', '');
		}
	}

	public function html(string $source, string $attributes): string {
		return "<script $attributes src=\"$source\"></script>";
	}

}
