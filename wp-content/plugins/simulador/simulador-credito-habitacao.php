<?php
/*
Plugin Name: Simulador Crédito Habitação
Description: Calcula prestação mensal de um crédito habitação
Version: 1.0
Author: Miguel Pinho
*/

add_action('rest_api_init', function () {
    register_rest_route('api/v1', '/mortgage/calculate', array(
        'methods' => 'POST',
        'callback' => 'calculate_mortgage',
        'permission_callback' => '__return_true',
        'args' => array(
            'loan_amount' => array(
                'required' => true,
                'validate_callback' => 'is_numeric'
            ),
            'duration_years' => array(
                'required' => false,
                'validate_callback' => 'is_numeric'
            ),
            'duration_months' => array(
                'required' => false,
                'validate_callback' => 'is_numeric'
            ),
            'rate' => array(
                'required' => true,
                'validate_callback' => 'is_numeric'
            ),
            'type' => array(
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return in_array($param, array('fixed', 'variable'));
                }
            ),
            'index_rate' => array(
                'required' => false,
                'validate_callback' => 'is_numeric'
            ),
            'spread' => array(
                'required' => false,
                'validate_callback' => 'is_numeric'
            ),
        ),
    ));
});

function calculate_mortgage($request) {
    $errors = validate_mortgage_input($request);
    if (!empty($errors)) {
        return new WP_Error('validation_error', 'Validation failed', ['status' => 422, 'errors' => $errors]);
    }

    $loan_amount = $request['loan_amount'];
    $duration_years = $request['duration_years'];
    $duration_months = $request['duration_months'];
    $rate = $request['rate'];
    $type = $request['type'];
    $index_rate = isset($request['index_rate']) ? $request['index_rate'] : null;
    $spread = isset($request['spread']) ? $request['spread'] : null;

    // Calcular a duração em meses
    $duration = $duration_years ? $duration_years * 12 : $duration_months;

    // Calcular a taxa de juros efetiva
    $effective_rate = $type == 'fixed' ? $rate : $index_rate + $spread;

    // Calcular a taxa de juros mensal
    $monthly_rate = $effective_rate / 12 / 100;

    // Calcular a prestação mensal usando a fórmula de amortização
    $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $duration)) / (pow(1 + $monthly_rate, $duration) - 1);

    // Inicializar variáveis para a tabela detalhada
    $balance = $loan_amount;
    $details = [];

    for ($month = 1; $month <= $duration; $month++) {
        $interest_paid = $balance * $monthly_rate; // Juros pagos
        $principal_paid = $monthly_payment - $interest_paid; // Capital amortizado
        $balance -= $principal_paid; // Saldo remanescente

        // Adicionar detalhes do mês à tabela
        $details[] = [
            'month' => $month,
            'interest_paid' => round($interest_paid, 2),
            'principal_paid' => round($principal_paid, 2),
            'remaining_balance' => round($balance, 2),
        ];
    }

    // Construir a resposta com metadata adicional
    $response = [
        'monthly_payment' => round($monthly_payment, 2),
        'loan_amount' => $loan_amount,
        'duration_months' => $duration,
        'duration_years' => $duration_years ? $duration_years : round($duration_months / 12, 2),
        'annual_rate' => $rate,
        'method' => 'french_amortization',
        'currency' => 'EUR',
        'metadata' => [
            'calculation_date' => date('c'),
            'formula' => 'M = P * [i(1 + i)^n] / [(1 + i)^n – 1]'
        ],
        'details' => $details // Tabela detalhada mês a mês
    ];

    // Retornar a resposta como JSON
    return $response;
}

function validate_mortgage_input($request) {
    $errors = [];

    $loan_amount = $request['loan_amount'];
    $duration_years = $request['duration_years'];
    $duration_months = $request['duration_months'];
    $rate = $request['rate'];
    $type = $request['type'];
    $index_rate = isset($request['index_rate']) ? $request['index_rate'] : null;
    $spread = isset($request['spread']) ? $request['spread'] : null;

    // Validar loan_amount
    if (!is_numeric($loan_amount) || $loan_amount <= 0) {
        $errors[] = ['field' => 'loan_amount', 'message' => 'Loan amount must be a positive number'];
    }

    // Validar duration
    if ($duration_years && !is_numeric($duration_years)) {
        $errors[] = ['field' => 'duration_years', 'message' => 'Duration years must be a numeric value'];
    }
    if ($duration_months && !is_numeric($duration_months)) {
        $errors[] = ['field' => 'duration_months', 'message' => 'Duration months must be a numeric value'];
    }
    if (!$duration_years && !$duration_months) {
        $errors[] = ['field' => 'duration', 'message' => 'Duration in years or months must be provided'];
    }

    // Validar rate
    if (!is_numeric($rate) || $rate <= 0) {
        $errors[] = ['field' => 'rate', 'message' => 'Rate must be a positive number'];
    }

    // Validar type
    if (!in_array($type, ['fixed', 'variable'])) {
        $errors[] = ['field' => 'type', 'message' => 'Type must be either "fixed" or "variable"'];
    }

    // Validar index_rate e spread para tipo variável
    if ($type == 'variable') {
        if (!is_numeric($index_rate) || $index_rate <= 0) {
            $errors[] = ['field' => 'index_rate', 'message' => 'Index rate must be a positive number'];
        }
        if (!is_numeric($spread) || $spread <= 0) {
            $errors[] = ['field' => 'spread', 'message' => 'Spread must be a positive number'];
        }
    }

    return $errors;
}
