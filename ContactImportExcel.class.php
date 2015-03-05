<?php
	require_once 'ContactImport.abstract.php';
	require_once 'Spreadsheet_Excel_Reader.class.php';


	class ContactImportExcel extends ContactImport
	{
		public static function file($filename)
		{
			$excel = new Spreadsheet_Excel_Reader();
			$excel->setOutputEncoding('CP1251');
			$excel->read($filename);

			// Only 1 sheet for now
			if (count($excel->sheets) <= 0)
				return false;
			$excel = array_shift($excel->sheets);

			if (isset($excel['cells']))
				return $excel['cells'];

			return false;
		}

		public static function format(&$data)
		{
			if (($data = parent::format($data)) === false)
				return false;

			$results = array();
			foreach ($data as $i => &$row)
			{
				$results[] = array(
					'cg_id' => (isset($row['group'])) ? ContactGroup::id_from_name($row['group']) : 1,
					'c_title' => (isset($row['title'])) ? $row['title'] : NULL,
					'c_first_name' => (isset($row['first-name'])) ? $row['first-name'] : NULL,
					'c_last_name' => (isset($row['last-name'])) ? $row['last-name'] : NULL,
					'c_email' => (isset($row['email-address'])) ? $row['email-address'] : NULL,
					'c_company' => (isset($row['company'])) ? $row['company'] : NULL,
					'c_notes' => (isset($row['notes'])) ? $row['notes'] : NULL,
					'c_address' => array(
						array(
							'at_id' => 1, // Home
							'ca_address' => (isset($row['address'])) ? $row['address'] : NULL,
							'ca_city' => (isset($row['city'])) ? $row['city'] : NULL,
							'ca_region' => (isset($row['province-state'])) ? $row['province-state'] : NULL,
							'ca_code' => (isset($row['postal-zip-code'])) ? $row['postal-zip-code'] : NULL,
							'ca_country' => (isset($row['country'])) ? $row['country'] : NULL
						),
						array(
							'at_id' => 2, // Work
							'ca_address' => (isset($row['work-address'])) ? $row['work-address'] : NULL,
							'ca_city' => (isset($row['work-city'])) ? $row['work-city'] : NULL,
							'ca_region' => (isset($row['work-province-state'])) ? $row['work-province-state'] : NULL,
							'ca_code' => (isset($row['work-postal-zip-code'])) ? $row['work-postal-zip-code'] : NULL,
							'ca_country' => (isset($row['work-country'])) ? $row['work-country'] : NULL
						)
					),

					'c_phone' => self::phones($row)
				);
			}

			return $results;
		}
	}