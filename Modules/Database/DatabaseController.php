<?php

namespace SiteBuilder\Modules\Database;

abstract class DatabaseController {
	private $server;
	private $name;
	private $user;
	private $password;
	private $isLoggingEnabled;
	private $logTableName;

	public final static function init(string $server, string $name, string $user, string $password): DatabaseController {
		return new static($server, $name, $user, $password);
	}

	private final function __construct(string $server, string $name, string $user, string $password) {
		$this->setServer($server);
		$this->setName($name);
		$this->setUser($user);
		$this->setPassword($password);
		$this->setLoggingEnabled(false);
	}

	public abstract function connect(): void;

	public abstract function getRow(string $table, string $id, string $columns = '*', string $primaryKey = 'ID'): array;

	public abstract function getRows(string $table, string $where, string $columns = '*', string $order = ''): array;

	public abstract function getVal(string $table, string $id, string $column, string $primaryKey = 'ID'): string;

	public abstract function insert(string $table, array $values, $primaryKey = 'ID'): int;

	public abstract function update(string $table, array $values, string $where): int;

	public abstract function delete(string $table, string $where): int;

	public abstract function log(string $type, string $query): bool;

	public final function getServer(): string {
		return $this->server;
	}

	private final function setServer($server): self {
		$this->server = $server;
		return $this;
	}

	public final function getName(): string {
		return $this->name;
	}

	private final function setName($name): self {
		$this->name = $name;
		return $this;
	}

	public final function getUser(): string {
		return $this->user;
	}

	private final function setUser($user): self {
		$this->user = $user;
		return $this;
	}

	public final function getPassword(): string {
		return $this->password;
	}

	private final function setPassword($password): self {
		$this->password = $password;
		return $this;
	}

	public final function isLoggingEnabled(): bool {
		return $this->isLoggingEnabled;
	}

	public function setLoggingEnabled(bool $isLoggingEnabled, string $logTableName = '__log'): self {
		$this->isLoggingEnabled = $isLoggingEnabled;

		if($this->isLoggingEnabled) {
			$this->setLogTableName($logTableName);
		} else {
			unset($this->logTableName);
		}

		return $this;
	}

	public final function getLogTableName(): string {
		return $this->logTableName;
	}

	private final function setLogTableName(string $logTableName): self {
		$this->logTableName = $logTableName;
		return $this;
	}

}

