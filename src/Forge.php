<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014-2019 British Columbia Institute of Technology
 * Copyright (c) 2019-2020 CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author     CodeIgniter Dev Team
 * @copyright  2019-2020 CodeIgniter Foundation
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://codeigniter.com
 * @since      Version 4.0.0
 * @filesource
 */

namespace Dgvirtual\CI4Firebird;

/**
 * Forge for Firebird
 */
class Forge extends \CodeIgniter\Database\Forge
{

	/**
	 * CREATE DATABASE statement
	 *
	 * @var string
	 */
	protected $createDatabaseStr = ''; // 'CREATE DATABASE %s CHARACTER SET %s COLLATE %s';

	/**
	 * CREATE DATABASE IF statement
	 *
	 * @var string
	 */
	protected $createDatabaseIfStr = ''; // 'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s';

	/**
	 * DROP CONSTRAINT statement
	 *
	 * @var string
	 */
	protected $dropConstraintStr = 'ALTER TABLE %s DROP CONSTRAINT %s';

	/**
	 * CREATE TABLE keys flag
	 *
	 * Whether table keys are created from within the
	 * CREATE TABLE statement.
	 *
	 * @var boolean
	 */
	protected $createTableKeys = true;

	/**
	 * UNSIGNED support
	 *
	 * @var array
	 */
	// Firebird does not support the UNSIGNED modifier.
	protected $_unsigned = [];

	/**
	 * Table Options list which required to be quoted
	 *
	 * @var array
	 */
	// Firebird has no table-level options comparable to MySQL's.
	protected $_quoted_table_options = [];

	/**
	 * NULL value representation in CREATE/ALTER TABLE statements
	 *
	 * @var string
	 */
	protected $_null = 'NULL';

	//--------------------------------------------------------------------

	/**
	 * CREATE TABLE attributes
	 *
	 * @param  array $attributes Associative array of table attributes
	 * @return string
	 */
	// Firebird does not support MySQL-style table attributes (CHARACTER SET, COLLATE, etc.).
	protected function _createTableAttributes(array $attributes): string
	{
		return '';
	}

	//--------------------------------------------------------------------

	/**
	 * ALTER TABLE
	 *
	 * @param  string $alter_type ALTER type
	 * @param  string $table      Table name
	 * @param  mixed  $field      Column definition
	 * @return string|string[]
	 */
	protected function _alterTable(string $alter_type, string $table, $field)
	{
		if ($alter_type === 'DROP')
		{
			return parent::_alterTable($alter_type, $table, $field);
		}

		$sqls         = [];
		$tableEscaped = $this->db->escapeIdentifiers($table);

		foreach ($field as $data)
		{
			if ($alter_type === 'ADD')
			{
				$fragment = $data['_literal'] !== false ? $data['_literal'] : $this->_processColumn($data);
				$sqls[]   = 'ALTER TABLE ' . $tableEscaped . ' ADD ' . $fragment;
			}
			else
			{
				// Firebird modifies one attribute at a time.
				$nameEscaped = $this->db->escapeIdentifiers($data['name']);

				if (! empty($data['new_name']))
				{
					$sqls[]      = 'ALTER TABLE ' . $tableEscaped . ' ALTER COLUMN ' . $nameEscaped . ' TO ' . $this->db->escapeIdentifiers($data['new_name']);
					$nameEscaped = $this->db->escapeIdentifiers($data['new_name']);
				}

				if (! empty($data['type']))
				{
					$sqls[] = 'ALTER TABLE ' . $tableEscaped . ' ALTER COLUMN ' . $nameEscaped . ' TYPE ' . $data['type'] . $data['length'];
				}
			}
		}

		return $sqls;
	}

	//--------------------------------------------------------------------

	/**
	 * Process column
	 *
	 * @param  array $field
	 * @return string
	 */
	protected function _processColumn(array $field): string
	{
		// Firebird does not support UNSIGNED, COMMENT, AFTER, or FIRST.
		// Columns are nullable by default — only NOT NULL is a valid keyword; never emit 'NULL'.
		$null = ($field['null'] === ' NOT NULL') ? ' NOT NULL' : '';

		return $this->db->escapeIdentifiers($field['name'])
			. ' ' . $field['type'] . $field['length']
			. $null
			. $field['default']
			. $field['auto_increment']
			. $field['unique'];
	}

	//--------------------------------------------------------------------

	/**
	 * Process indexes
	 *
	 * @param  string $table (ignored)
	 * @return string
	 */
	// In Firebird, only UNIQUE constraints can be defined inline within CREATE TABLE.
	// Non-unique indexes must be created separately with CREATE INDEX.
	protected function _processIndexes(string $table, bool $asQuery = false): array
	{
		$sqls = [''];

		for ($i = 0, $c = count($this->keys); $i < $c; $i++)
		{
			$index = $asQuery ? $i : 0;

			if (is_array($this->keys[$i]))
			{
				for ($i2 = 0, $c2 = count($this->keys[$i]); $i2 < $c2; $i2++)
				{
					if (! isset($this->fields[$this->keys[$i][$i2]]))
					{
						unset($this->keys[$i][$i2]);
					}
				}
			}
			elseif (! isset($this->fields[$this->keys[$i]]))
			{
				unset($this->keys[$i]);
				continue;
			}

			is_array($this->keys[$i]) || $this->keys[$i] = [$this->keys[$i]];

			if (in_array($i, $this->uniqueKeys))
			{
				$name         = $this->db->escapeIdentifiers(implode('_', $this->keys[$i]));
				$cols         = implode(', ', $this->db->escapeIdentifiers($this->keys[$i]));
				$sqls[$index] .= ",\n\tCONSTRAINT " . $name . ' UNIQUE (' . $cols . ')';
			}
			// Non-unique indexes are silently skipped here; use db->query('CREATE INDEX ...') after table creation.
		}

		$this->keys = [];

		return $sqls;
	}

	//--------------------------------------------------------------------
}
