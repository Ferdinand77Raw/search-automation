<?php

class ContactSearch {
    private $apiBaseUrl;
    private $searchUrlMap;

    public function __construct() {
        $this->apiBaseUrl = "https://devapi.endato.com/";
        $this->searchUrlMap = [
            "DevAPICallerID" => "/Phone/Enrich",
            "DevAPIContactEnrich" => "/Contact/Enrich",
            "DevAPIIDVerification" => "/Identity/Verify_Id"
        ];
    }

    public function search($searchType, $data) {
        if (isset($data['Phone'])) {
            $data['Phone'] = preg_replace('/^\+1\s*/', '', $data['Phone']);
        }

        $searchUrlType = $this->searchUrlMap[$searchType];
        $url = $this->apiBaseUrl . $searchUrlType;

        $ch = curl_init($url);
        $jsonData = json_encode($data);

        $headers = [
            'accept: application/json',
            'content-type: application/json',
            'galaxy-ap-name: <YOUR APP NAME>',
            'galaxy-ap-password: <YOUR APP PASSWORD>',
            'galaxy-client-type: Galaxy Client Type',
            'galaxy-search-type: ' . $searchType
        ];

        curl_setopt($ch, CURLOPT_HEADER, false); // No incluir encabezados en la respuesta
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        if (curl_error($ch)) {
            echo curl_error($ch);
        }

        curl_close($ch);

        return $result;
    }
}

$dataEnrichment = new ContactSearch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["searchType"])) {
        $searchType = $_POST["searchType"];
    } else {
        echo "Tipo de búsqueda no especificado.";
        exit;
    }

    if (isset($_FILES["csv"]["tmp_name"])) {
        $csvFile = $_FILES["csv"]["tmp_name"];

        // Abrir el archivo CSV para lectura
        $file = fopen($csvFile, "r");

        // Abrir el archivo CSV para escritura
        $fp = fopen("EndatoResults.csv", "w");

        // Escribir los encabezados en el archivo CSV
        fputcsv($fp, ["firstName", "middleName", "lastName", "age", "street", "unit", "city", "state", "zip", "email", "isEmailValidated", "isBusiness", "phoneNumber", "type", "isConnected", "firstReportedDate", "lastReportedDate"]);

        // Leer el archivo CSV línea por línea
        while (($line = fgetcsv($file)) !== false) {
            // Obtener el número de teléfono de la tercera columna
            $phone = trim($line[2]);

            // Verificar el tipo de búsqueda y llamar a la función correspondiente
            switch ($searchType) {
                case "DevAPICallerID":
                    $data = ["Phone" => $phone];
                    break;
                case "DevAPIContactEnrich":
                
                    $data = [
                        "FirstName" => "",
                        "LastName" => "",
                        "Phone" => preg_replace('/^\+1\s*/', '', $phone),
                        "Address" => [
                            "addressLine1" => "",
                            "addressLine2" => ""
                        ]
                    ];
                    break;
                case "DevAPIIDVerification":
                    
                    $data = [
                        "FirstName" => "",
                        "MiddleName" => "",
                        "LastName" => "",
                        "Dob" => "",
                        "Age" => "",
                        "Address" => [
                            "addressLine1" => "",
                            "addressLine2" => ""
                        ],
                        "PhoneNumber" => preg_replace('/^\+1\s*/', '', $phone),
                        "Email" => ""
                    ];
                    break;
                default:
                    echo "Tipo de búsqueda no válido.";
                    exit;
            }

            // Llamar a la función search con los datos del formulario
            $result = $dataEnrichment->search($searchType, $data);

            // Decodificar el resultado JSON
            $resultArray = json_decode($result, true);

            // Verificar si hay resultados válidos
            if (isset($resultArray['person'])) {
                // Obtener los datos de la persona
                $person = $resultArray['person'];

                // Obtener los datos de la dirección
                $address = $person['address'];

                // Obtener los datos del teléfono
                $phoneData = $person['phone'];

                // Escribir los datos en el archivo CSV
                fputcsv($fp, [
                    $person['name']['firstName'],
                    $person['name']['middleName'],
                    $person['name']['lastName'],
                    $person['age'],
                    $address['street'],
                    $address['unit'],
                    $address['city'],
                    $address['state'],
                    $address['zip'],
                    $person['email'],
                    $person['isEmailValidated'],
                    $person['isBusiness'],
                    $phoneData['phoneNumber'],
                    $phoneData['type'],
                    $phoneData['isConnected'],
                    $phoneData['firstReportedDate'],
                    $phoneData['lastReportedDate']
                ]);
            }
        }

        fclose($file);
        fclose($fp);

        echo "Los resultados se han guardado correctamente en EndatoResults.csv";
    } else {
        echo "Archivo CSV no proporcionado.";
    }
}

?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" enctype="multipart/form-data">
    <label for="searchType">Tipo de búsqueda:</label>
    <select name="searchType" id="searchType">
        <option value="DevAPICallerID">DevAPICallerID</option>
        <option value="DevAPIContactEnrich">DevAPIContactEnrich</option>
        <option value="DevAPIIDVerification">DevAPIIDVerification</option>
    </select>
    <br><br>
    <label for="csv">Archivo CSV:</label>
    <input type="file" name="csv" id="csv">
    <br><br>
    <input type="submit" value="Buscar">
</form>
