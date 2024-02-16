<?php

class AutomateSearchPDL
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function performSearch($phoneNumbers)
    {
        // Abrir el archivo CSV de salida para escritura
        $outputFileName = 'PDLResult.csv';
        $outputFile = fopen($outputFileName, 'w');

        // Escribir las cabezas de las columnas en el archivo CSV
        fputcsv($outputFile, array_keys($this->getSampleData()));

        // Recorrer cada número de teléfono
        foreach ($phoneNumbers as $phoneNumber) {
            // Eliminar paréntesis, guiones y espacios del número de teléfono
            $cleanedPhoneNumber = preg_replace('/[\(\)\-\s]/', '', $phoneNumber);

            // Realizar la búsqueda con el número de teléfono limpio
            $result = $this->searchByPhoneNumber($cleanedPhoneNumber);

            // Escribir los datos en el archivo CSV
            if (!empty($result)) {
                fputcsv($outputFile, $result);
            }
        }

        // Cerrar el archivo CSV de salida
        fclose($outputFile);

        echo "Los resultados se han guardado en el archivo $outputFileName";
    }

    private function searchByPhoneNumber($phoneNumber)
    {
        $data = [
            'dataset' => 'all',
            'size' => 10,
            'sql' => "SELECT * FROM person WHERE (phone_numbers = '$phoneNumber')"
        ];

        $jsonData = json_encode($data);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.peopledatalabs.com/v5/person/search');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Api-Key: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }

        curl_close($ch);

        // Decodificar la respuesta JSON
        $data = json_decode($response, true);

        // Retornar los datos
        return $data['data'][0] ?? [];
    }

    // Esta función devuelve un array de ejemplo con las cabezas de las columnas como claves
    private function getSampleData()
    {
        return [
            "id" => "",
            "full_name" => "",
            "first_name" => "",
            "middle_initial" => "",
            "middle_name" => "",
            "last_initial" => "",
            "last_name" => "",
            "gender" => "",
            "birth_year" => "",
            "birth_date" => "",
            "linkedin_url" => "",
            "linkedin_username" => "",
            "linkedin_id" => "",
            "facebook_url" => "",
            "facebook_username" => "",
            "facebook_id" => "",
            "twitter_url" => "",
            "twitter_username" => "",
            "github_url" => "",
            "github_username" => "",
            "work_email" => "",
            "personal_emails" => "",
            "recommended_personal_email" => "",
            "mobile_phone" => "",
            "industry" => "",
            "job_title" => "",
            "job_title_role" => "",
            "job_title_sub_role" => "",
            "job_title_levels" => "",
            "job_company_id" => "",
            "job_company_name" => "",
            "job_company_website" => "",
            "job_company_size" => "",
            "job_company_founded" => "",
            "job_company_industry" => "",
            "job_company_linkedin_url" => "",
            "job_company_linkedin_id" => "",
            "job_company_facebook_url" => "",
            "job_company_twitter_url" => "",
            "job_company_location_name" => "",
            "job_company_location_locality" => "",
            "job_company_location_metro" => "",
            "job_company_location_region" => "",
            "job_company_location_geo" => "",
            "job_company_location_street_address" => "",
            "job_company_location_address_line_2" => "",
            "job_company_location_postal_code" => "",
            "job_company_location_country" => "",
            "job_company_location_continent" => "",
            "job_last_updated" => "",
            "job_start_date" => "",
            "location_name" => "",
            "location_locality" => "",
            "location_metro" => "",
            "location_region" => "",
            "location_country" => "",
            "location_continent" => "",
            "location_street_address" => "",
            "location_address_line_2" => "",
            "location_postal_code" => "",
            "location_geo" => "",
            "location_last_updated" => "",
            "phone_numbers" => [""],
            "emails" => "",
            "interests" => "",
            "skills" => "",
            "location_names" => [""],
            "regions" => [""],
            "countries" => [""],
            "street_addresses" => [""],
            "experience" => "",
            "education" => "",
            "profiles" => [""],
        ];
    }
}

// Verificar si se ha enviado el formulario con un archivo CSV
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['csv_file'])) {
    // Obtener el archivo CSV
    $csvFile = $_FILES['csv_file']['tmp_name'];

    // Leer el archivo CSV y obtener los números de teléfono
    $phoneNumbers = [];
    if (($handle = fopen($csvFile, "r")) !== false) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Suponiendo que el número de teléfono se encuentra en la primera columna del CSV
            $phoneNumbers[] = $data[0];
        }
        fclose($handle);
    }

    // Crear una instancia de AutomateSearchPDL con la clave API
    $apiKey = '<YOUR PEOPLEDATALABS API KEY>';
    $search = new AutomateSearchPDL($apiKey);

    // Realizar la búsqueda con los números de teléfono obtenidos del archivo CSV
    $search->performSearch($phoneNumbers);
}

?>

<!-- Formulario HTML para cargar el archivo CSV -->
<form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <label for="csv_file">Selecciona un archivo CSV:</label>
    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
    <input type="submit" value="Buscar">
</
