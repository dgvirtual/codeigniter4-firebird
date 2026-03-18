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

use CodeIgniter\Database\BasePreparedQuery;

/**
 * Prepared query for Firebird
 */
class PreparedQuery extends BasePreparedQuery 
{

	/**
	 * Prepares the query against the database, and saves the connection
	 * info necessary to execute the query later.
	 *
	 * @param string $sql
	 * @param array  $options Passed to the connection's prepare statement.
	 *
	 * @return mixed
	 */
	public function _prepare(string $sql, array $options = [])
	{
		$this->statement = $this->db->connID->prepare($sql);

		if ($this->statement === false)
		{
			$error             = $this->db->connID->errorInfo();
			$this->errorCode   = $error[1] ?? 0;
			$this->errorString = $error[2] ?? 'Unknown prepare error';
		}

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Executes the prepared query with the given bound data.
	 *
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function _execute(array $data): bool
	{
		if (is_null($this->statement))
		{
			throw new \BadMethodCallException('You must call prepare before trying to execute a prepared statement.');
		}

		return $this->statement->execute(array_values($data));
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the PDOStatement for the prepared query.
	 *
	 * @return mixed
	 */
	public function _getResult()
	{
		return $this->statement;
	}

	//--------------------------------------------------------------------


    /**
     * Deallocate prepared statements.
     */
    protected function _close(): bool
    {
		if ($this->statement instanceof \PDOStatement)
		{
			$this->statement->closeCursor();
		}
		$this->statement = null;

        return true;
    }	
}
