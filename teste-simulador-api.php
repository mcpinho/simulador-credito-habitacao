<?php
// URL da API REST - ajuste conforme apropriado
$url = 'endereco-site/wp-json/api/v1/mortgage/calculate';

// Parâmetros da requisição
$data = [
    'loan_amount' => 200000,
    'duration_years' => 30,
    'rate' => 3,
    'type' => 'fixed',
];

// Inicializa o cURL
$ch = curl_init($url);

// Configura as opções cURL
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Executa a requisição
$response = curl_exec($ch);

// Verifica se houve erro
if ($response === false) {
    $error = curl_error($ch);
    echo "Error: $error";
} else {
    // Exibe a resposta da API
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code >= 200 && $http_code < 300) {
        echo "Success: ";
        echo "<pre>";
        print_r(json_decode($response, true));
        echo "JSON:".$response;
        echo "</pre>";

        $result = json_decode($response, true);

        // Criar e salvar CSV
        $csv_file = fopen("mortgage_details.csv", "w");
        if ($csv_file === false) {
            echo "Error creating CSV file.";
            exit;
        }

        // Escrever BOM UTF-8 para garantir encoding correto
        fputs($csv_file, "\xEF\xBB\xBF");

        // Escrever cabeçalhos no CSV usando ';' como separador
        fputcsv($csv_file, ['Mês', 'Juros', 'Capital Amortizado', 'Saldo Remanescente'], ';');

        // Escrever dados no CSV usando ';' como separador, com € no final dos valores monetários
        foreach ($result['details'] as $detail) {
            fputcsv($csv_file, [
                $detail['month'], 
                str_replace('.', ',', $detail['interest_paid']) . ' €', 
                str_replace('.', ',', $detail['principal_paid']) . ' €', 
                str_replace('.', ',', $detail['remaining_balance']) . ' €'
            ], ';');
        }

        // Fechar o arquivo CSV
        fclose($csv_file);

        echo "CSV exportado com sucesso. Verifique o arquivo mortgage_details.csv.";
    } else {
        echo "HTTP Error Code: $http_code\n";
        echo "Response: $response";
    }
}

// Fecha o cURL
curl_close($ch);
?>
