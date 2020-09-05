<?php

namespace SiteBuilder\Authentication;

use SiteBuilder\PageElement\PageElement;

class AuthenticationElement extends PageElement {
	public $html;

	public static function newInstance(): self {
		return new self();
	}

	public function __construct() {
		parent::__construct();
		$this->html = '';
	}

	public function getDependencies(): array {
		return array();
	}

	public function getContent(): string {
		return $this->html;
	}

}
