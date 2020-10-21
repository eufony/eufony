<?php

namespace SiteBuilder\Core\CMS;

use ErrorException;
use Throwable;

/**
 * <p>
 * The content management system (CMS) of SiteBuilder handles the inclusion of the PHP content,
 * header and footer files of all of the websites webpages, and provides a convenient way to store
 * metadata about the pages themselves.
 * </p>
 * <p>
 * To use the CMS, initialize an instace of this class using CMSManager::init(), passing in an
 * instance of a PageHierarchy in the configuration parameters, and call its run() method. The
 * CMSManager will automatically find the necessary things it needs to manage your website (which is
 * of course also configureable). In addition, the CMSManager will also automatically use the 'p'
 * GET parameter from the URL to determine which page should be shown. As such, you only need to
 * define one 'index.php' for each website you have on your server. The rest of the page-specific
 * elements and scripts should go into the corresponding content files.
 * </p>
 * <p>
 * Note that CMSManager is a Singleton class, meaning only one instance of it can be initialized at
 * a time.
 * </p>
 *
 * @author Alpin Gencer
 * @namespace SiteBuilder\Core\CMS
 * @see PageHierarchy
 * @see CMSManager::init()
 * @see CMSManager::run()
 */
class CMSManager {
	/**
	 * Static instance field for Singleton code design in PHP
	 *
	 * @var CMSManager
	 */
	private static $instance;
	/**
	 * The directory in which SiteBuilder itself lives, relative to the document root.
	 * Defaults to '/SiteBuilder/'
	 *
	 * @var string
	 */
	private $frameworkDirectory;
	/**
	 * The directory in which the content files are defined, relative to the document root.
	 * Defaults to '/Content/'
	 *
	 * @var string
	 */
	private $contentDirectory;
	/**
	 * The page hierarchy that this class manages
	 *
	 * @var PageHierarchy
	 */
	private $hierarchy;
	/**
	 * An associative array defining the path of the page to display on any given HTTP error code
	 *
	 * @var array
	 */
	private $errorPagePaths;
	/**
	 * Wether to set a SiteBuilder custom exception handler to automatically show an error page to
	 * the user on a server error.
	 * Defaults to true
	 *
	 * @var bool
	 */
	private $showErrorPageOnException;
	/**
	 * The current page path, as defined by the 'p' GET parameter.
	 * If no 'p' parameter is set, the CMSManager will redirect the user to the default page.
	 *
	 * @var string
	 */
	private $currentPagePath;
	/**
	 * The default page path.
	 * Defaults to 'home'
	 *
	 * @var string
	 */
	private $defaultPagePath;

	/**
	 * Returns an instance of CMSManager
	 *
	 * @param array $config The configuration parameters to use.
	 *        Please note that 'hierarchy' is a required parameter and must pass in a PageHierarchy
	 *        object.
	 * @return CMSManager The initialized instance
	 */
	public static function init(array $config = []): CMSManager {
		if(isset(CMSManager::$instance)) {
			throw new ErrorException("An instance of CMSManager has already been instantiated!");
		}

		CMSManager::$instance = new self($config);
		return CMSManager::$instance;
	}

	/**
	 * Normalizes a directory path string, parsin '.', '..' and '\\' strings and adding slashes to
	 * the beginning and end
	 *
	 * @param string $directory The path to process
	 * @return string The normalized directory path
	 */
	public static function normalizeDirectoryString(string $directory): string {
		$directory = PageHierarchy::normalizePathString($directory);
		return "/$directory/";
	}

	/**
	 * Constructor for the CMSManager.
	 * To get an instance of this class, use CMSManager::init().
	 * The constructor also sets the superglobal '__SiteBuilder_CMSManager' to easily get this
	 * instance.
	 *
	 * @see CMSManager::init()
	 */
	private function __construct(array $config = []) {
		$GLOBALS['__SiteBuilder_CMSManager'] = &$this;

		if(!isset($config['frameworkDirectory'])) $config['frameworkDirectory'] = '/SiteBuilder/';
		if(!isset($config['contentDirectory'])) $config['contentDirectory'] = '/Content/';
		if(!isset($config['hierarchy'])) throw new ErrorException("The required configuration parameter 'hierarchy' has not been set!");
		if(!isset($config['showErrorPageOnException'])) $config['showErrorPageOnException'] = true;
		if(!isset($config['defaultPagePath'])) $config['defaultPagePath'] = 'home';

		$this->setFrameworkDirectory($config['frameworkDirectory']);
		$this->setContentDirectory($config['contentDirectory']);
		$this->setHierarchy($config['hierarchy']);
		$this->setDefaultPagePath($config['defaultPagePath']);
		$this->clearErrorPagePaths();
		$this->setShowErrorPageOnException($config['showErrorPageOnException']);

		if(isset($_GET['p']) && !empty($_GET['p'])) {
			$this->setCurrentPagePath($_GET['p']);
		} else {
			// Redirect to set 'p' parameter in request URI
			$this->redirectToPage($this->defaultPagePath, true);
		}
	}

	/**
	 * Runs the manager to execute so that the processes that the CMSManager handles are executed.
	 * Please note that this function must be run in order for the CMSManager to work.
	 */
	public function run(): void {
		// Check if page exists in hierarchy
		// If not, show error 404: Page not found
		if(!$this->hierarchy->isPageDefined($this->currentPagePath)) {
			if($this->isErrorPagePathDefined(404)) {
				$this->redirectToPage($this->getErrorPagePath(404));
			} else if($this->isErrorPagePathDefined(400)) {
				$this->redirectToPage($this->getErrorPagePath(400));
			} else {
				$this->showDefaultErrorPage(404);
				return;
			}
		}

		// Include content files for the page, the global header and footer,
		// and the page header and footer
		// Global header and footer paths are relative to page content directory
		// Page header and footer paths are relative to current page path
		$requirePaths = array();

		if($this->hierarchy->isGlobalAttributeDefined('global-header')) array_push($requirePaths, $this->hierarchy->getGlobalAttribute('global-header'));
		if($this->hierarchy->isPageAttributeDefined($this->currentPagePath, 'header')) {
			array_push($requirePaths, dirname($this->currentPagePath) . '/' . $this->hierarchy->getPageAttribute($this->currentPagePath, 'header'));
		}

		array_push($requirePaths, $this->currentPagePath);

		if($this->hierarchy->isPageAttributeDefined($this->currentPagePath, 'footer')) {
			array_push($requirePaths, dirname($this->currentPagePath) . '/' . $this->hierarchy->getPageAttribute($this->currentPagePath, 'footer'));
		}
		if($this->hierarchy->isGlobalAttributeDefined('global-footer')) array_push($requirePaths, $this->hierarchy->getGlobalAttribute('global-footer'));


		foreach($requirePaths as $path) {
			// Check if content file exists
			// If yes, include it
			// If no, show error 501: Page not implemented
			if($this->isContentFileDefined($path)) {
				// File found, include
				require $this->getContentFilePath($path);
			} else {
				// A required file was not found, show 501 page
				trigger_error("The path '" . $path . "' does not have a corresponding content file!", E_USER_WARNING);

				if($this->isErrorPagePathDefined(501)) {
					$this->redirectToPage($this->getErrorPagePath(501));
				} else if($this->isErrorPagePathDefined(500)) {
					$this->redirectToPage($this->getErrorPagePath(500));
				} else {
					$this->showDefaultErrorPage(501);
					return;
				}
			}
		}

		// Restore default exception handler
		$this->setShowErrorPageOnException(false);
	}

	/**
	 * Redirect the user to a given page path, optionally also keeping other GET parameters.
	 * The redirection works using a HTTP 303 redirect header sent to the browser.
	 * Please note that this will also halt the script after execution.
	 *
	 * @param string $pagePath The page path to redirect to
	 * @param bool $keepGETParams Wether to keep the GET parameters
	 */
	public function redirectToPage(string $pagePath, bool $keepGETParams = false): void {
		$pagePath = PageHierarchy::normalizePathString($pagePath);

		if($keepGETParams) {
			// Get HTTP query without 'p' parameter
			$params = http_build_query(array_diff_key($_GET, array(
					'p' => ''
			)));

			if(!empty($params)) $params = '&' . $params;
		} else {
			$params = '';
		}

		$uri = '?p=' . $pagePath . $params;

		// If redirecting to the same URI, show 508 page to avoid infinite redirecting
		$requestURIWithoutGETParameters = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
		if($requestURIWithoutGETParameters . $uri === $_SERVER['REQUEST_URI']) {
			trigger_error('Infinite loop detected while redirecting! Showing the default error 508 page to avoid infinite redirecting.', E_USER_WARNING);
			$this->showDefaultErrorPage(508);
		}

		// Redirect and die
		header('Location:' . $uri, true, 303);
		die();
	}

	/**
	 * Check if a given content file exists
	 *
	 * @param string $pagePath The path in the content directory to search for
	 * @return bool The boolean result
	 */
	public function isContentFileDefined(string $pagePath): bool {
		try {
			$this->getContentFilePath($pagePath);
			return true;
		} catch(ErrorException $e) {
			return false;
		}
	}

	/**
	 * Get the path for the content file of a given page path
	 *
	 * @param string $pagePath The path in the content directory to search for
	 * @return string The computed path
	 */
	public function getContentFilePath(string $pagePath): string {
		$pagePath = PageHierarchy::normalizePathString($pagePath);
		$contentFilePath = $_SERVER['DOCUMENT_ROOT'] . $this->contentDirectory . $pagePath . '.php';

		// Check if content file exists
		// If no, throw error: Content file not found
		if(!file_exists($contentFilePath)) {
			throw new ErrorException("The given path '$pagePath' does not have a corresponding content file!");
		}

		return $contentFilePath;
	}

	/**
	 * Check if the error page path for a given HTTP error code is defined
	 *
	 * @param int $errorCode The HTTP error code to check for
	 * @return bool The boolean result
	 */
	public function isErrorPagePathDefined(int $errorCode): bool {
		try {
			$this->getErrorPagePath($errorCode);
			return true;
		} catch(ErrorException $e) {
			return false;
		}
	}

	/**
	 * Get the error page path for a given HTTP error code.
	 * If no custom page path is defined, the manager will also check to see if the SiteBuilder
	 * default error code page path can be used instead.
	 *
	 * @param int $errorCode The HTTP error code to search for
	 * @return string The defined page path
	 */
	public function getErrorPagePath(int $errorCode): string {
		// Check if error page path is defined for the given error code
		// If no, check if the sitebuilder default path for error pages is defined in the hierarchy
		// If also no, throw error: No error page path defined
		if(!isset($this->errorPagePaths[$errorCode])) {
			try {
				$this->setErrorPagePath($errorCode, 'error/' . $errorCode);
			} catch(ErrorException $e) {
				throw new ErrorException("The page path for the error code '$errorCode' is not defined!");
			}
		}

		return $this->errorPagePaths[$errorCode];
	}

	/**
	 * Getter for the error page paths
	 *
	 * @return array An associative array with the HTTP error codes and the error page paths
	 */
	public function getAllErrorPagePaths(): array {
		return $this->errorPagePaths;
	}

	/**
	 * Set the error page path for a given HTTP error code.
	 * The error page path must be defined in the page hierarchy and must have a corresponding
	 * content file.
	 *
	 * @param int $errorCode The HTTP error code to define the error page path for
	 * @param string $pagePath The error page path to use
	 * @return self Returns itself for chaining other functions
	 */
	public function setErrorPagePath(int $errorCode, string $pagePath): self {
		$pagePath = PageHierarchy::normalizePathString($pagePath);

		// Check if error page is in hierarchy
		// If no, throw error: Cannot use undefined error page
		if(!$this->hierarchy->isPageDefined($pagePath)) {
			throw new ErrorException("The given error page path '$pagePath' is not in the page hierarchy!");
		}

		// Check if error page has a content file
		// If no, throw error: Cannot use error page without its content file
		if(!$this->isContentFileDefined($pagePath)) {
			throw new ErrorException("The given error page path '$pagePath' does not have a corresponding content file!");
		}

		$this->errorPagePaths[$errorCode] = $pagePath;
		return $this;
	}

	/**
	 * Undefine the error page path for a given HTTP error code
	 *
	 * @param int $errorCode The error code to undefine the error page path for
	 * @return self Returns itself for chaining other functions
	 */
	public function removeErrorPagePath(int $errorCode): self {
		if(isset($this->errorPagePaths[$errorCode])) {
			unset($this->errorPagePaths[$errorCode]);
		} else {
			trigger_error("No error page path with the given error code '$errorCode' to remove is defined!", E_USER_NOTICE);
		}

		return $this;
	}

	/**
	 * Undefine all error page paths that were previously set
	 *
	 * @return self Returns itself for chaining other functions
	 */
	public function clearErrorPagePaths(): self {
		$this->errorPagePaths = array();
		return $this;
	}

	/**
	 * Outputs a default error page to the browser according to the given HTTP error code.
	 * Please note that this will also halt the script after execution.
	 *
	 * @param int $errorCode The HTTP error code to output
	 */
	public function showDefaultErrorPage(int $errorCode): void {
		http_response_code($errorCode);
		$errorPage = DefaultErrorPage::init($errorCode);
		echo $errorPage->getHTML();
		die();
	}

	/**
	 * Getter for the framework directory
	 *
	 * @return string
	 */
	public function getFrameworkDirectory(): string {
		return $this->frameworkDirectory;
	}

	/**
	 * Setter for the framework directory
	 *
	 * @param string $frameworkDirectory
	 * @return self Returns itself for chaining other functions
	 */
	public function setFrameworkDirectory(string $frameworkDirectory): self {
		$this->frameworkDirectory = CMSManager::normalizeDirectoryString($frameworkDirectory);
		return $this;
	}

	/**
	 * Getter for the content directory
	 *
	 * @return string
	 */
	public function getContentDirectory(): string {
		return $this->contentDirectory;
	}

	/**
	 * Setter for the content directory
	 *
	 * @param string $contentDirectory
	 * @return self Returns itself for chaining other functions
	 */
	public function setContentDirectory(string $contentDirectory): self {
		$this->contentDirectory = CMSManager::normalizeDirectoryString($contentDirectory);
		return $this;
	}

	/**
	 * Getter for the page hierarchy
	 *
	 * @return PageHierarchy
	 */
	public function getHierarchy(): PageHierarchy {
		return $this->hierarchy;
	}

	/**
	 * Setter for the page hierarchy
	 *
	 * @param PageHierarchy $hierarchy
	 * @return self Returns itself for chaining other functions
	 */
	private function setHierarchy(PageHierarchy $hierarchy): self {
		$this->hierarchy = $hierarchy;
		return $this;
	}

	/**
	 * Getter for wether the CMSManager shows an error page on an uncaught exception
	 *
	 * @return bool
	 */
	public function isShowErrorPageOnException(): bool {
		return $this->showErrorPageOnException;
	}

	/**
	 * Setter for wether the CMSManager should show an error page on an uncaught exception
	 *
	 * @param bool $showErrorPageOnException
	 * @return self Returns itself for chaining other functions
	 */
	public function setShowErrorPageOnException(bool $showErrorPageOnException): self {
		$this->showErrorPageOnException = $showErrorPageOnException;

		if($this->showErrorPageOnException) {
			// Set custom exception handler
			set_exception_handler(function (Throwable $e) {
				// Log exception
				error_log('Uncaught ' . $e->__toString(), 4);

				// Show error page
				if($this->isErrorPagePathDefined(500)) {
					$this->redirectToPage($this->getErrorPagePath(500));
				} else {
					$this->showDefaultErrorPage(500);
				}
			});
		} else {
			// Restore previous exception handler
			restore_exception_handler();
		}

		return $this;
	}

	/**
	 * Getter for the current page path
	 *
	 * @return string
	 */
	public function getCurrentPagePath(): string {
		return $this->currentPagePath;
	}

	/**
	 * Setter for the current page path
	 *
	 * @param string $currentPagePath
	 * @return self Returns itself for chaining other functions
	 */
	private function setCurrentPagePath(string $currentPagePath): self {
		if(empty($currentPagePath)) {
			throw new ErrorException("The given page path is empty!");
		}

		$p = PageHierarchy::normalizePathString($currentPagePath);

		if($p !== $currentPagePath) {
			// Redirect to normalize page path in request URI
			$this->redirectToPage($p, true);
		}

		$lastCharInURI = substr($_SERVER['REQUEST_URI'], -1);
		if($lastCharInURI === '&') {
			// Redirect to remove trailing '&' in request URI
			$this->redirectToPage($p, true);
		}

		$this->currentPagePath = $p;
		return $this;
	}

	/**
	 * Getter for the default page path
	 *
	 * @return string
	 */
	public function getDefaultPagePath(): string {
		return $this->defaultPagePath;
	}

	/**
	 * Setter for the default page path
	 *
	 * @param string $defaultPagePath
	 * @return self
	 */
	private function setDefaultPagePath(string $defaultPagePath): self {
		$this->defaultPagePath = PageHierarchy::normalizePathString($defaultPagePath);
		return $this;
	}

}

