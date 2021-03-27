<?php
/**************************************************
 *            The Eufony PHP Framework            *
 *         Copyright (c) 2021 Alpin Gencer        *
 *      Refer to LICENSE.md for a full notice     *
 **************************************************/

namespace Eufony\Session;

use Eufony\Config\Config;
use Eufony\Config\ConfigurationException;
use Eufony\FileSystem\Directory;
use Eufony\Utils\Traits\StaticOnly;

class Session {
	use StaticOnly;

	public static function start(): void {
		// Assert that PHP sessions are not disabled: The framework and its modules rely on sessions
		if(session_status() === PHP_SESSION_DISABLED) {
			throw new ConfigurationException('Server misconfiguration: PHP sessions must be enabled');
		}

		// Set session handler
		$session_handlers = Directory::files('file://' . __DIR__ . '/Handlers');
		$session_handlers = array_map(fn($handler) => strtolower(basename($handler, 'SessionHandler.php')), $session_handlers);

		try {
			$session_handler = Config::get('SESSION_HANDLER', expected: $session_handlers) ?? 'files';
			$session_handler_class = __NAMESPACE__ . '\Handlers\\' . ucwords($session_handler) . 'SessionHandler';
		} catch(ConfigurationException) {
			throw new ConfigurationException("Unknown session handler configured");
		}

		ini_set('session.save_handler', $session_handler);
		session_save_path(call_user_func([$session_handler_class, 'savePath']));

		// Get session timeout
		$session_timeout = Config::get('SESSION_TIMEOUT', expected: 'int');

		// Set garbage collection of unused sessions
		// Refer to: https://stackoverflow.com/a/654547
		ini_set('session.gc_probability', 1);
		ini_set('session.gc_divisor', 100);
		if(Config::exists('SESSION_TIMEOUT')) {
			ini_set('session.gc_maxlifetime', $session_timeout);
		}

		// Start session
		$cookie_params = ['samesite' => 'Lax'];
		if(Config::exists('SESSION_TIMEOUT')) $cookie_params['lifetime'] = $session_timeout;
		session_set_cookie_params($cookie_params);
		session_start();

		// Server-side session timeout logic
		if(Config::exists('SESSION_TIMEOUT')) {
			$last_activity = $_SESSION['SESSION_LAST_ACTIVITY'] ?? null;

			if(isset($last_activity) && (time() - $last_activity + 1) > $session_timeout) {
				session_unset();
				session_destroy();
				session_start();
			}
		}

		$_SESSION['SESSION_LAST_ACTIVITY'] = time();
	}

	public static function exists(string $name): bool {

	}

	public static function get(string $name, bool $required = false, string|array $expected = null): mixed {

	}

	public static function set(string $name, mixed $value): void {

	}

}
