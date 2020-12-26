<?php

namespace SiteBuilder\Modules\Translation\Controllers;

use SiteBuilder\Modules\Database\DatabaseModule;
use SiteBuilder\Modules\Translation\TranslationController;
use ErrorException;

/**
 * A DatabaseTranslationController transates tokens by fetching them from a database
 *
 * @author Alpin Gencer
 * @namespace SiteBuilder\Modules\Translation
 */
class DatabaseTranslationController extends TranslationController {
	/**
	 * The database table containing the translations
	 *
	 * @var string
	 */
	private $tokenTableName;
	/**
	 * The name of the primary key column representing the token IDs
	 *
	 * @var string
	 */
	private $primaryKey;

	/**
	 * Returns an instance of DatabaseTranslationController
	 *
	 * @param string $tokenTableName The database name of the tokens table
	 * @param string $primaryKey The column name representing the token IDs
	 * @return DatabaseTranslationController The initialized instance
	 */
	public static function init(string $tokenTableName, string $primaryKey = 'ID'): DatabaseTranslationController {
		return new self($tokenTableName, $primaryKey);
	}

	/**
	 * Constructor for the DatabaseTranslationController.
	 * To get an instance of this class, use DatabaseTranslationController::init()
	 *
	 * @param string $tokenTableName The database name of the tokens table
	 * @param string $primaryKey The column name representing the token IDs
	 */
	private function __construct(string $tokenTableName, string $primaryKey) {
		// Check if database module is initialized
		// If no, throw error: Cannot use DatabaseTranslationController without DatabaseModule
		if(!$GLOBALS['__SiteBuilder_ModuleManager']->isModuleInitialized(DatabaseModule::class)) {
			throw new ErrorException("The DatabaseModule must be initialized when using a DatabaseTranslationController!");
		}

		$this->setTokenTableName($tokenTableName);
		$this->setPrimaryKey($primaryKey);
	}

	/**
	 * {@inheritdoc}
	 * @see \SiteBuilder\Modules\Translation\TranslationController::translate()
	 */
	public function translate($id, $lang): string {
		$database = $GLOBALS['__SiteBuilder_ModuleManager']->getModuleByClass(DatabaseModule::class)->db();
		$token = $database->getVal($this->tokenTableName, $id, $lang, $this->primaryKey);

		// Check if token is empty
		// If yes, trigger error: Error while fetching token from database
		if(empty($token)) {
			$message = "Error while fetching token ID '$id' language '$lang' from database!";
			trigger_error($message, E_USER_WARNING);
			return $message;
		}

		return $token;
	}

	/**
	 * Getter for the token table name
	 *
	 * @return string
	 * @see DatabaseTranslationController::$tokenTableName
	 */
	public function getTokenTableName(): string {
		return $this->tokenTableName;
	}

	/**
	 * Setter for the token table name
	 *
	 * @param string $tokenTableName
	 * @see DatabaseTranslationController::$tokenTableName
	 */
	private function setTokenTableName(string $tokenTableName): void {
		$this->tokenTableName = $tokenTableName;
	}

	/**
	 * Getter for the primary key
	 *
	 * @return string
	 * @see DatabaseTranslationController::$primaryKey
	 */
	public function getPrimaryKey(): string {
		return $this->primaryKey;
	}

	/**
	 * Setter for the primary key
	 *
	 * @param string $primaryKey
	 * @see DatabaseTranslationController::$primaryKey
	 */
	private function setPrimaryKey(string $primaryKey): void {
		$this->primaryKey = $primaryKey;
	}

}
