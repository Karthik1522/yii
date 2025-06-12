<?php

set_error_handler(function ($errno, $errstr) {
    echo "User Error [$errno]: $errstr\n";
    return true;
},  E_USER_NOTICE);

$expenses = [
    ['amount' => '200.50', 'category' => 'food', 'date' => '2025-04-01'],
    ['amount' => 150, 'category' => 'travel', 'date' => 'April 3, 2025'],
    ['amount' => 'bad_data', 'category' => 'food', 'date' => '2025-04-04'],
    ['amount' => 300, 'category' => 'health', 'date' => '2025-04-03'],
    ['amount' => 99.99, 'category' => 'misc', 'date' => 'invalid-date'],
];

$get_categories = function() use($expenses) : array{
    $categories = [];

    foreach($expenses as $item){
        $category = $item['category'];
        
        $key = array_search($category, $categories);

        if($key === false){
            array_push($categories, $category);
        }
    }

    return $categories; 
};


$expensesSummary = function() use($expenses, $get_categories) {
    $categories = $get_categories();

    $summary = array_flip($categories);

    foreach($summary as $category => $value){
        $summary[$category] = 0;
    }
   
    foreach($expenses as $expense){
        $amount = $expense['amount'];
        $category = $expense['category'];
        $date = $expense['date'];

        if(!is_numeric($amount)){
            trigger_error("Invalid amount '{$amount}' for category '$category'", E_USER_NOTICE);
            continue;
        }

        if (strtotime($date) === false) {
            trigger_error("Invalid date '{$date}' for category '$category'", E_USER_NOTICE);
            continue;
        }

        $numericAmount = (float)$amount;

        $summary[$category] += $numericAmount;
    }

    return $summary;

};


// print_r($expensesSummary());
// echo $expensesSummary();