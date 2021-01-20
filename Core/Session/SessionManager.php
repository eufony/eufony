<?php
/**************************************************
 *          The SiteBuilder PHP Framework         *
 *         Copyright (c) 2021 Alpin Gencer        *
 *      Refer to LICENSE.md for a full notice     *
 **************************************************/

namespace SiteBuilder\Core\Session;

use ErrorException;
use SiteBuilder\Core\FrameworkManager;
use SiteBuilder\Core\Website\WebsiteManager;
use SiteBuilder\Utils\Traits\ManagedObject;
use SiteBuilder\Utils\Traits\Singleton;

final class SessionManager {
	public const SESSION_LAST_ACTIVITY = 'LastActivity';
	public const CONFIG_TIMEOUT = 'session-timeout';

	use ManagedObject;
	use Singleton;

	public function __construct() {
		$this->setAndAssertManager(FrameworkManager::class);
		$this->assertSingleton();

		// Check if PHP sessions are disabled on the server
		// If yes, throw error: PHP sessions must be enabled
		if(session_status() === PHP_SESSION_DISABLED) {
			throw new ErrorException('Cannot use the SiteBuilder framework if PHP sessions are disabled by the server!');
		}

		// Start session
		session_set_cookie_params(['samesite' => 'Lax']);
		session_start();

		// Restart user session if timed out
		$session_timeout = FrameworkManager::instance()->config(SessionManager::CONFIG_TIMEOUT, null, expected_type: 'integer');
		if($session_timeout !== null) {
			$last_activity = $this->get(SessionManager::SESSION_LAST_ACTIVITY, global: true);

			if(isset($last_activity) && (time() - $last_activity + 1) > $session_timeout) {
				session_unset();
				session_destroy();
				session_start();
			}
		}

		$this->set(SessionManager::SESSION_LAST_ACTIVITY, time(), global: true);
	}

	public function variableName(string $variable_name, bool $global = false): string {
		$subsite = $global ? 'shared' : WebsiteManager::instance()->subsite();
		return '__SiteBuilder_' . $subsite . '_' . $variable_name;
	}

	public function get(string $variable_name, bool $global = false): mixed {
		return $_SESSION[$this->variableName($variable_name, global: $global)] ?? null;
	}

	public function set(string $variable_name, mixed $value, bool $global = false): void {
		$_SESSION[$this->variableName($variable_name, global: $global)] = $value;
	}

	public function unset(string $variable_name, bool $global = false): void {
		unset($_SESSION[$this->variableName($variable_name, global: $global)]);
	}
}
