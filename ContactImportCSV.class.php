<?php
	require_once 'ContactImport.abstract.php';


	class ContactImportCSV extends ContactImport
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
			foreach ($data as &$row)
			{
				$results[] = array(
					'c_first_name' => (isset($row['first-name'])) ? $row['first-name'] : NULL,
					'c_last_name' => (isset($row['last-name'])) ? $row['last-name'] : NULL,
					'c_email' => (isset($row['email-address'])) ? $row['email-address'] : NULL,
					'c_phone' => self::phones($row),
					'c_notes' => (isset($row['notes'])) ? $row['notes'] : NULL
				);
			}

			return $results;
		}
	}