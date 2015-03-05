<?php
	require_once 'ContactImport.abstract.php';
	require_once 'VCard.class.php';


	class ContactImportVCard extends ContactImport
	{
		public static function file($filename)
		{
			$return = array();

			if (($data = parent::file($filename)) === false)
				return false;

			while (($obj = new VCard()) && $obj->parse($data))
			{
				if ($obj->getProperty('N') !== false)
					$return[] = $obj;
			}

			return $return;
		}

		public static function format(&$data)
		{
			$results = array();

			foreach ($data as &$vcard)
			{
				$row = $vcard->getProperties();
				
				if (is_array($row['N']))
				{
					// Last Name, First Name, Middle Name, Title
					list($lname, $fname, ) = $row['N'];
				}
				else
				{
					$fname = $row['N'];
					$lname = NULL;
				}
				
				$address = (isset($row['ADR'])) ? $row['ADR'] : NULL;

				$results[] = array(
					'c_first_name' => $fname,
					'c_last_name' => $lname,
					'c_email' => (isset($row['EMAIL'])) ? $row['EMAIL'] : NULL,
					'c_phone' => self::phones($vcard),
					'c_notes' => (isset($row['NOTE'])) ? $row['NOTE'] : NULL,
					
					'c_address' => array(
						array(
							'at_id' => 3,
							'ca_address' => (isset($address[0])) ? $address[0] : NULL,
							'ca_city' => (isset($address[1])) ? $address[1] : NULL,
							'ca_region' => (isset($address[2])) ? $address[2] : NULL,
							'ca_code' => (isset($address[3])) ? $address[3] : NULL,
							'ca_country' => (isset($address[4])) ? $address[4] : NULL
						)
					)
				);
			}
			
			return $results;
		}
		
		public static function phones(&$vcard)
		{
			$phones = array();
			
			if (($property = $vcard->getProperty('TEL')) !== false)
			{
				if (is_array($property))
				{
					$tmp = &$property;
					foreach ($tmp as &$property)
					{
						self::phones_inner($property, $phones);
					}
				}
				else
					self::phones_inner($property, $phones);
			}
			
			return $phones;
		}
		private static function phones_inner(&$property, &$phones)
		{
			if (count($property->params) > 0)
			{
				foreach ($property->params as $param => &$value)
				{
					if ($param != 'TYPE')
					{
						unset($property->params[$param]);
						continue;
					}
					
					if (is_array($value))
						$value = array_shift($value);
					
					if (($pt_id = PhoneType::GetIDFromName($value)) <= 0)
						$pt_id = 7;
					
					$phones[$pt_id] = $property->value;
				}
			}
			else
				$phones[] = $property->value;
		}
	}