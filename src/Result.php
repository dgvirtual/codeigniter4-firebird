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

use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Entity\Entity;

/**
 * Result for Firebird
 */
class Result extends BaseResult implements ResultInterface
{
	/**
	 * Buffered rows for PDO (which has no native cursor seek).
	 *
	 * @var array
	 */
	protected array $bufferedRows = [];

	/**
	 * Current position in the buffer.
	 *
	 * @var int
	 */
	protected int $bufferPointer = 0;

	/**
	 * Whether rows have been loaded into the buffer.
	 *
	 * @var bool
	 */
	protected bool $bufferFilled = false;

	/**
	 * Fetch all rows from the PDOStatement into the internal buffer.
	 */
	protected function bufferResults(): void
	{
		if (! $this->bufferFilled && $this->resultID instanceof \PDOStatement) {
			$this->bufferedRows = $this->resultID->fetchAll(\PDO::FETCH_ASSOC);
			$this->bufferFilled = true;
		}
	}

	/**
	 * Gets the number of fields in the result set.
	 *
	 * @return integer
	 */
	public function getFieldCount(): int
	{
		return $this->resultID->columnCount();
	}

	//--------------------------------------------------------------------

	/**
	 * Generates an array of column names in the result set.
	 *
	 * @return array
	 */
	public function getFieldNames(): array
	{
		$fieldNames = [];
		for ($i = 0, $c = $this->resultID->columnCount(); $i < $c; $i++)
		{
			$fieldNames[] = $this->resultID->getColumnMeta($i)['name'];
		}

		return $fieldNames;
	}

	//--------------------------------------------------------------------

	/**
	 * Generates an array of objects representing field meta-data.
	 *
	 * @return array
	 */
	public function getFieldData(): array
	{
		$retVal = [];
		for ($i = 0, $c = $this->resultID->columnCount(); $i < $c; $i++)
		{
			$meta          = $this->resultID->getColumnMeta($i);
			$obj           = new \stdClass();
			$obj->name       = $meta['name'];
			$obj->type       = $meta['native_type'] ?? null;
			$obj->max_length = $meta['len'] ?? null;
			$obj->primary_key = false;
			$obj->default    = null;
			$retVal[]      = $obj;
		}

		return $retVal;
	}

	//--------------------------------------------------------------------

	/**
	 * Frees the current result.
	 *
	 * @return void
	 */
	public function freeResult()
	{
		if ($this->resultID instanceof \PDOStatement)
		{
			$this->resultID->closeCursor();
			$this->resultID = false;
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Moves the internal pointer to the desired offset. This is called
	 * internally before fetching results to make sure the result set
	 * starts at zero.
	 *
	 * @param integer $n
	 *
	 * @return mixed
	 */
	public function dataSeek(int $n = 0): bool
	{
		$this->bufferResults();
		$this->bufferPointer = $n;

		return isset($this->bufferedRows[$n]) || $n === count($this->bufferedRows);
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the result set as an array.
	 *
	 * Overridden by driver classes.
	 *
	 * @return mixed
	 */
	protected function fetchAssoc()
	{
		$this->bufferResults();

		if (isset($this->bufferedRows[$this->bufferPointer])) {
			return $this->bufferedRows[$this->bufferPointer++];
		}

		return false;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the result set as an object.
	 *
	 * Overridden by child classes.
	 *
	 * @param string $className
	 *
	 * @return object|boolean|Entity
	 */
	protected function fetchObject(string $className = 'stdClass')
	{
		$row = $this->fetchAssoc();

		if ($row === false) {
			return false;
		}

		if (is_subclass_of($className, Entity::class)) {
			return (new $className())->setAttributes($row);
		}

		if ($className === 'stdClass') {
			return (object) $row;
		}

		$obj = new $className();
		foreach ($row as $key => $val) {
			$obj->{$key} = $val;
		}

		return $obj;
	}

	//--------------------------------------------------------------------

	public function getNumRows(): int
	{
		if (! is_int($this->numRows)) {
			$this->bufferResults();
			$this->numRows = count($this->bufferedRows);
		}

		return $this->numRows;
	}

	//--------------------------------------------------------------------
}
