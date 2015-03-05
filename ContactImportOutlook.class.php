<?php
	require_once 'ContactImport.abstract.php';
	require_once 'Spreadsheet_Excel_Reader.class.php';
	
	
	class ContactImportOutlook extends ContactImport
	{
		public static function file($filename)
		{
			if (($fh = fopen($filename, 'rb')) === false)
				return false;

			$content = array();
			while (($buffer = fgetcsv($fh)) !== false)
			{
				if (count(array_filter($buffer)) <= 0)
					continue;

				$content[] = $buffer;
			}
			fclose($fh);

			return $content;
		}
		
		public static function format(&$data)
		{
			if (($data = parent::format($data)) === false)
				return false;
			
			$results = array();
			foreach ($data as $i => &$row)
			{
				$results[] = array(
					'c_title' => (isset($row['title'])) ? $row['title'] : NULL,
					'c_first_name' => (isset($row['first-name'])) ? $row['first-name'] : NULL,
					'c_last_name' => (isset($row['last-name'])) ? $row['last-name'] : NULL,
					'c_email' => (isset($row['e-mail-address'])) ? $row['e-mail-address'] : NULL,
					'c_company' => (isset($row['company'])) ? $row['company'] : NULL,
					'c_private' => (isset($row['private'])) ? self::boolean($row['private']) : NULL,
					'c_notes' => (isset($row['notes'])) ? $row['notes'] : NULL,
					
					'c_phone' => array(
						1 => (isset($row['home-phone'])) ? $row['home-phone'] : NULL,
						2 => (isset($row['business-phone'])) ? $row['business-phone'] : NULL,
						3 => (isset($row['mobile-phone'])) ? $row['mobile-phone'] : NULL,
						5 => (isset($row['pager'])) ? $row['pager'] : NULL
					),
					
					'c_address' => array(
						array(
							'at_id' => 1, // Home
							'ca_address' => (isset($row['home-street'])) ? $row['home-street'] : NULL,
							'ca_city' => (isset($row['home-city'])) ? $row['home-city'] : NULL,
							'ca_region' => (isset($row['home-state'])) ? $row['home-state'] : NULL,
							'ca_code' => (isset($row['home-postal-code'])) ? $row['home-postal-code'] : NULL,
							'ca_country' => (isset($row['home-country-region'])) ? $row['home-country-region'] : NULL
						),
						array(
							'at_id' => 2, // Work
							'ca_address' => (isset($row['business-street'])) ? $row['business-street'] : NULL,
							'ca_city' => (isset($row['business-city'])) ? $row['business-city'] : NULL,
							'ca_region' => (isset($row['business-state'])) ? $row['business-state'] : NULL,
							'ca_code' => (isset($row['business-postal-code'])) ? $row['business-postal-code'] : NULL,
							'ca_country' => (isset($row['business-country-region'])) ? $row['business-country-region'] : NULL
						)
					)
				);
			}
			
			return $results;
		}
	}