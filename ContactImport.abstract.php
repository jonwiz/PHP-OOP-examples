<?php
	abstract class ContactImport
	{
		public static function file($filename)
		{
			return file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		}

		public static function format(&$data)
		{
			return self::assoc($data);
		}
		
		public static function contacts(&$data, &$error=NULL)
		{
			global $WhizUser, $Agent;
			$u_id = (($aid = (int)$Agent->Getu_id()) > 0) ? $aid : (int)$WhizUser->Getu_id();
			
			foreach ($data as $i => $row)
			{
				$c = new Contact();
				
				// Required
				$c->Setu_id($u_id);
				$c->Setc_first_name($row['c_first_name']);
				$c->Setc_last_name($row['c_last_name']);
				$c->Setc_email($row['c_email']);
				foreach ($row['c_phone'] as $pt_id => $cp_number)
				{
					$cp = new ContactPhone();
					$cp->Setpt_id($pt_id);
					$cp->Setcp_number($cp_number);
					
					$c->Setc_phone($cp);
				}
				$c->Setc_notes($row['c_notes']);
				
				// Optional
				if (isset($row['cg_id']))
					$c->Setcg_id($row['cg_id']);
				if (isset($row['c_title']))
					$c->Setc_title($row['c_title']);
				if (isset($row['c_company']))
					$c->Setc_company($row['c_company']);
				if (isset($row['c_facebook']))
					$c->Setc_facebook($row['c_facebook']);
				if (isset($row['c_twitter']))
					$c->Setc_facebook($row['c_twitter']);
				if (isset($row['c_linkedin']))
					$c->Setc_facebook($row['c_linkedin']);
				if (isset($row['c_googleplus']))
					$c->Setc_facebook($row['c_googleplus']);
				
				if (isset($row['c_address']))
				{
					foreach ($row['c_address'] as &$address)
					{
						// Nothing to do since only at_id is set
						if (count(array_filter($address)) <= 1)
							continue;
						
						$ca = new ContactAddress();
						$ca->Setat_id($address['at_id']);
						if (isset($address['ca_address']))
							$ca->Setca_address($address['ca_address']);
						if (isset($address['ca_city']))
							$ca->Setca_city($address['ca_city']);
						if (isset($address['ca_region']))
							$ca->Setca_region($address['ca_region']);
						if (isset($address['ca_code']))
							$ca->Setca_code($address['ca_code']);
						if (isset($address['ca_country']))
							$ca->Setca_country($address['ca_country']);
						
						$c->Setc_address($ca);
					}
				}
				
				if (strlen($tmp = $c->Create()) > 0)
				{
					if (stristr($tmp, 'duplicate entry') !== false)
						$tmp = '\'' . $row['c_first_name'] . ' ' . $row['c_last_name'] . '\' could not be imported. Email already exists.';
					
					$error = $tmp;
					return false;
				}
			}
			
			return true;
		}

		// Creates an associative array based on the input data
		//
		public static function assoc(&$data)
		{
			if (!is_array($data) || count($data) <= 0)
				return false;

			$data = array_values($data);
			$headings = array_shift($data);

			foreach ($data as $dk => $dv)
			{
				$tmp = array();
				foreach ($dv as $dvk => &$dvv)
				{
					if (!array_key_exists($dvk, $headings))
						continue;
					
					$tmp[self::key($headings[$dvk])] = trim($dvv);
				}

				if (count($tmp) > 0)
					$data[$dk] = $tmp;
				else
					unset($data[$dk]);
			}

			return $data;
		}

		public static function phones(&$row)
		{
			$phones = array();
			
			foreach ($row as $key => &$value)
			{
				if (!preg_match('/(?:([a-z]*).*phone$|^phone-(.+))/i', $key, $matches))
					continue;
				
				$phones[PhoneType::GetIDFromName(array_pop($matches))] = $value;
			}
			
			$phones = array_unique(array_filter($phones));

			return $phones;
		}


		// Creates a slug of the passed string for easier reference in an array
		// Note: This function may cause an array to have duplicates due to the string manipulation
		//
		protected static function key($string)
		{
			if (preg_match_all('/(?:[A-Z0-9]*[a-z0-9]+|[A-Z0-9]+)/', $string, $matches, PREG_SET_ORDER))
			{
				foreach ($matches as $i => $match)
				{
					$matches[$i] = array_shift($match);
				}
				$string = implode(' ', $matches);
			}

			$string = strtolower($string);
			$string = preg_replace('/[[:punct:]]/', '', $string);
			$string = preg_replace('/[^[:alnum:]]/', ' ', $string);
			$string = preg_replace('/\s{2, }/', '', $string);
			$string = str_replace(' ', '-', $string);

			return $string;
		}
		
		protected static function boolean($string)
		{
			if (is_numeric($string))
				return ((int)$string !== 0);
			
			$string = trim($string);
			$string = strtolower($string);
			
			return ($string == 't' || $string == 'true' || $string == 'y' || $string == 'yes');
		}
	}