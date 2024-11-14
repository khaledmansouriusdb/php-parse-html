<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Check if file is uploaded and is HTML
	if (isset($_FILES['file']) && $_FILES['file']['type'] === 'text/html') {
		$htmlContent = file_get_contents($_FILES['file']['tmp_name']);

		// Basic validation to ensure the structure contains specific tags
		if (
			strpos($htmlContent, '<div class="report">') !== false &&
			strpos($htmlContent, '<table>') !== false
		) {
			// Move to parsing and database insertion
			parseAndInsertData($htmlContent);
		} else {
			echo "Invalid file structure.";
		}
	} else {
		echo "Please upload a valid HTML file.";
	}
}

// Function to parse and insert data
function parseAndInsertData($htmlContent)
{
	// Load HTML using DOMDocument
	$dom = new DOMDocument();
	@$dom->loadHTML($htmlContent);

	// Extract report date and report number
	$reportDate = $dom->getElementsByTagName('p')->item(0)->nodeValue;  // First <p> is the report date
	$reportNumber = $dom->getElementsByTagName('p')->item(1)->nodeValue; // Second <p> is the report number

	// Connect to PostgreSQL
	$conn = new PDO("pgsql:host=localhost;dbname=testdb", "postgres", "123456");

	// Parse table rows
	$rows = $dom->getElementsByTagName('tr');
	foreach ($rows as $index => $row) {
		// Skip header row
		if ($index === 0)
			continue;

		// Extract data from cells
		$cells = $row->getElementsByTagName('td');
		$id = $cells->item(0)->nodeValue;
		$name = $cells->item(1)->nodeValue;
		$value = $cells->item(2)->nodeValue;

		// Insert into database
		$stmt = $conn->prepare("INSERT INTO records (report_date, report_number, record_id, name, value) VALUES (?, ?, ?, ?, ?)");
		$stmt->execute([$reportDate, $reportNumber, $id, $name, $value]);
	}

	echo "Data inserted successfully!";
}
?>