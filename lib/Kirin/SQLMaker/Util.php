<?php

class KirinSQLMakerUtil
{
	static function ref ($value)
	{
        if ( is_array($value) ) {
            if (sizeof($value) == 0) {
                return 'ARRAY';
            }

            $keys = implode( array_keys($value) );

            return is_numeric($keys) ? 'ARRAY' : 'HASH';
        }
        else if ( is_object($value) ) {
            return get_class($value);
        }

        return 'SCALAR';
	}

	static function quote_identifier ($label, $quote_char, $name_sep)
	{
		if ($label == '*') {
			return $label;
		}
		else if ( !$name_sep ) {
			return $label;
		}
		else {
			$split  = preg_split("/\Q$name_sep\E/", $label);
			$retval = array( );
			foreach ($split as $s) { $retval[ ] = $quote_char . $s . $quote_char; }
			return implode($name_sep, $retval);
		}
	}
}
