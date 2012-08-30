<?php
/**
 * @package ActiveRecord
 */
namespace ActiveRecord;

/**
 * Adapter for Firebird.
 *
 * @package ActiveRecord
 */
class FirebirdAdapter extends Connection
{
	static $DEFAULT_PORT = 3050;

    var $dbdriver = 'firebird';
    var $_escape_char = '';
	
    // clause and character used for LIKE escape sequences
    var $_like_escape_str = " ESCAPE '%s' ";
    var $_like_escape_chr = '!';
    
	 
	 /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword = ' ASC';
	
    // database specific random keyword
    /**
     * Connection String
     *
     * @access  private
     * @return  string
     */	
	
	public function limit($sql, $offset, $limit)
	{
        if ($offset == '')
        {
            $offset = 0;
        }
        $sql = substr_replace($sql, "select first $limit skip $offset ", stripos($sql, 'select'), 6);
        return $sql;
	}

	public function query_column_info($table)
	{
		$table = strtoupper($table);
        return 'SELECT rel_fld.rdb$field_name as FIELD_NAME FROM rdb$relations rel JOIN rdb$relation_fields rel_fld ON rel_fld.rdb$relation_name = rel.rdb$relation_name JOIN rdb$fields fld ON rel_fld.rdb$field_source = fld.rdb$field_name WHERE rel.rdb$relation_name = \''.$table.'\' ORDER BY rel_fld.rdb$field_position, rel_fld.rdb$field_name';
	}

	public function query_for_tables()
	{
		 $sql = "SELECT RDB$RELATION_NAME FROM RDB$RELATIONS WHERE RDB$SYSTEM_FLAG = 0";
        if ($prefix_limit !== FALSE AND $this->dbprefix != '')
        {
            $sql .= " AND RDB$RElATION_NAME LIKE '".$this->escape_like_str($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
        }
        return $sql;
	}

	public function create_column(&$column)
	{
		$c = new Column();
		$c->inflected_name	= Inflector::instance()->variablize($column['field']);
		$c->name			= $column['field'];
		$c->nullable		= ($column['null'] === 'YES' ? true : false);
		$c->pk				= ($column['key'] === 'PRI' ? true : false);
		$c->auto_increment	= ($column['extra'] === 'auto_increment' ? true : false);

		if ($column['type'] == 'timestamp' || $column['type'] == 'datetime')
		{
			$c->raw_type = 'datetime';
			$c->length = 19;
		}
		elseif ($column['type'] == 'date')
		{
			$c->raw_type = 'date';
			$c->length = 10;
		}
		elseif ($column['type'] == 'time')
		{
			$c->raw_type = 'time';
			$c->length = 8;
		}
		else
		{
			preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/',$column['type'],$matches);

			$c->raw_type = (count($matches) > 0 ? $matches[1] : $column['type']);

			if (count($matches) >= 4)
				$c->length = intval($matches[3]);
		}

		$c->map_raw_type();
		$c->default = $c->cast($column['default'],$this);

		return $c;
	}

	public function set_encoding($charset='')
	{
		$this->query('DEFAULT CHARACTER SET WIN1252 COLLATION WIN1252;');
	}

	public function accepts_limit_and_order_for_update_and_delete()
	{
		return true; 
	}

	public function native_database_types()
	{
		return array(
			'primary_key' 	=> 'integer',
			'string' 		=> array('name' => 'varchar', 'length' => 255	),
			'integer' 		=> array('name' => 'integer'					),
			'float' 		=> array('name' => 'float'						),
			'numeric' 		=> array('name' => 'numeric', 'length' => 18	),
			'datetime' 		=> array('name' => 'datetime'					),
			'timestamp' 	=> array('name' => 'datetime'					),
			'time' 			=> array('name' => 'time'						),
			'date' 			=> array('name' => 'date'						),
			'binary' 		=> array('name' => 'blob'						),
			'boolean' 		=> array('name' => 'tinyint', 'length' => 1		)
		);
	}
}
?>
