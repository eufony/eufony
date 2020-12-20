<?php

namespace SiteBuilder\Modules\Components\Form;

use SiteBuilder\Core\CM\Dependency\CSSDependency;
use SiteBuilder\Core\CM\Dependency\JSDependency;
use SiteBuilder\Modules\Database\DatabaseModule;
use ErrorException;

class ManyFieldFormFieldset extends FormFieldset {
	private $secondaryTableDatabaseName;
	private $minNumFields;
	private $maxNumFields;
	private $primaryKey;
	private $foreignKey;

	public static function init(string $prompt, string $secondaryTableDatabaseName): FormFieldset {
		return new self($prompt, $secondaryTableDatabaseName);
	}

	function __construct(string $prompt, string $secondaryTableDatabaseName) {
		parent::__construct($prompt);
		$this->setSecondaryTableDatabaseName($secondaryTableDatabaseName);
		$this->clearMinNumFields();
		$this->clearMaxNumFields();
		$this->clearPrimaryKey();
		$this->clearForeignKey();
	}

	public function getDependencies(): array {
		$manyFieldDependencies = array(
				JSDependency::init('Form/many-fields.js', 'defer'),
				CSSDependency::init('Form/many-fields.css')
		);

		return array_merge($manyFieldDependencies, parent::getDependencies());
	}

	public function getContent(): string {
		// Generate prompt HTML
		$html = '<tr><td>' . $this->getPrompt() . ':</td>';


		// Generate fieldset HTML
		$minNumFields = ($this->minNumFields !== 0) ? ' data-min-fields="' . $this->minNumFields . '"' : '';
		$maxNumFields = ($this->maxNumFields !== 0) ? ' data-max-fields="' . $this->maxNumFields . '"' : '';

		$html .= '<td class="sitebuilder-many-fields"' . $minNumFields . $maxNumFields . '>';

		// Generate template fieldset HTML
		$html .= '<fieldset class="sitebuilder-template-fieldset">';

		foreach($this->getFormFields() as $field) {
			$html .= $field->getContent($field->getDefaultValue());
		}

		$html .= '</fieldset>';

		// Generate existing fieldset HTML
		if($this->getParentForm()->isNewObject()) {
			$count = $this->minNumFields;
		} else {
			$database = $GLOBALS['__SiteBuilder_ModuleManager']->getModuleByClass(DatabaseModule::class)->db();
			$table = $this->secondaryTableDatabaseName;
			$where = '`' . $this->getForeignKey() . '`="' . $this->getParentForm()->getObjectID() . '"';
			$order = $this->primaryKey;
			$rows = $database->getRows($table, $where, '*', $order);
			$count = max($this->minNumFields, sizeof($rows));
		}

		for($i = 0; $i < $count; $i++ ) {
			$html .= '<fieldset>';

			foreach($this->getFormFields() as $field) {
				if($this->getParentForm()->isNewObject() || !isset($rows[$i])) {
					$prefillValue = $field->getDefaultValue();
				} else {
					// Fetch existing data from database
					$prefillValue = $rows[$i][$field->getColumn()];
				}

				$html .= $field->getContent($prefillValue, '_' . ($i + 1));
			}

			$html .= '</fieldset>';
		}

		$html .= '</td></tr>';
		return $html;
	}

	public function process(): array {
		// Delete previous entries
		if(!$this->getParentForm()->isNewObject()) {
			$this->delete();
		}

		// Get database controller
		$database = $GLOBALS['__SiteBuilder_ModuleManager']->getModuleByClass(DatabaseModule::class)->db();

		// If there are no form fields, return
		if(empty($this->getFormFields())) {
			return array();
		}

		// For each defined fieldset
		// Check first added form field post variable to search for additional fieldsets
		for($i = 1; isset($_POST[$this->getFormFields()[0]->getFormFieldName() . '_' . $i]); $i++ ) {
			// Add foreign ID
			$values = array(
					$this->foreignKey => $this->getParentForm()->getObjectID()
			);

			// Add form field values
			foreach($this->getFormFields() as $field) {
				$values = array_merge($values, array(
						$field->getColumn() => $_POST[$field->getFormFieldName() . '_' . $i]
				));
			}

			// Insert new entries
			$database->insert($this->secondaryTableDatabaseName, $values, $this->primaryKey);
		}

		// Parent form has nothing to process, return empty array
		return array();
	}

	public function delete(): void {
		// Delete entries in secondary table
		$database = $GLOBALS['__SiteBuilder_ModuleManager']->getModuleByClass(DatabaseModule::class)->db();
		$database->delete($this->secondaryTableDatabaseName, $this->foreignKey . "='" . $this->getParentForm()->getObjectID() . "'");
	}

	public function getSecondaryTableDatabaseName(): string {
		return $this->secondaryTableDatabaseName;
	}

	private function setSecondaryTableDatabaseName(string $secondaryTableDatabaseName): void {
		$this->secondaryTableDatabaseName = $secondaryTableDatabaseName;
	}

	public function getMinNumFields(): int {
		return $this->minNumFields;
	}

	public function setMinNumFields(int $minNumFields): self {
		// Check if the given minimum number of fields is less than 0
		// If yes, throw error: Minimum number cannot be negative
		if($minNumFields < 0) {
			throw new ErrorException("The minimum number of fields must not be smaller than 0!");
		}

		$this->minNumFields = $minNumFields;
		return $this;
	}

	public function clearMinNumFields(): self {
		$this->setMinNumFields(0);
		return $this;
	}

	public function getMaxNumFields(): int {
		return $this->maxNumFields;
	}

	public function setMaxNumFields($maxNumFields): self {
		// Check if the given maximum number of fields is not 0 and less than the minimum
		// If yes, throw error: Maximum cannot be less than minimum
		if($maxNumFields !== 0 && $maxNumFields < $this->minNumFields) {
			throw new ErrorException("The maximum number of fields must not be smaller than the minimum number of fields!");
		}

		$this->maxNumFields = $maxNumFields;
		return $this;
	}

	public function clearMaxNumFields(): self {
		$this->setMaxNumFields(0);
		return $this;
	}

	public function getPrimaryKey(): string {
		return $this->primaryKey;
	}

	public function setPrimaryKey(string $primaryKey): self {
		$this->primaryKey = $primaryKey;
		return $this;
	}

	public function clearPrimaryKey(): self {
		$this->setPrimaryKey('ID');
		return $this;
	}

	public function getForeignKey(): string {
		return $this->foreignKey;
	}

	public function setForeignKey(string $foreignKey): self {
		$this->foreignKey = $foreignKey;
		return $this;
	}

	public function clearForeignKey(): self {
		$this->setForeignKey('FID');
		return $this;
	}

}
