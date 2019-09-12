<?php

$countArgv = count($argv);
if($countArgv < 2){
    echo "usage:\nphp find_best_pairs.php results.csv\n\t--min-pairs=5\n\t--min-profit=50\n\t--min-trades=40\n\t--min-winrate=50\n\t--sort=profit,trades,winrate\n";
    exit(0);
}

$options = [];
$i = 2;
while($i < $countArgv){
    if(substr($argv[$i], 0, 2) == '--' && ($pos = strpos($argv[$i], '=')) !== false){
        $options[substr($argv[$i], 2, $pos - 2)] = substr($argv[$i], $pos + 1);
    }
    $i++;
}

$csvFile = $argv[1];
if(!file_exists($csvFile)){
    echo "File csv not found\n";
    exit(0);
}

if(($handle = fopen($csvFile, 'r')) === false){
    echo "Cant open csv file\n";
    exit(0);
};

$delimiter = false;
$data = fgetcsv($handle, 600, ',');
if(count($data) > 6){
    $delimiter = ',';
}
else {
    rewind($handle);
    $data = fgetcsv($handle, 600, ';');
    if(count($data) > 6){
        $delimiter = ';';
    }
}

if(!$delimiter){
    echo "File $csvFile is not a valid csv\n";
    exit(0);
};

$inputIndexes = [];
$idx = 8;
while(isset($data[$idx]) && strpos($data[$idx], 'Input') !== false){
    $inputIndexes[] = $idx;
    $idx++;
}

if(empty($inputIndexes)){
    echo "Inputs column not found\n";
    exit(0);
}

$combs = [];
if (($handle = fopen($csvFile, "r")) !== false) {
    while (($data = fgetcsv($handle, 600, $delimiter)) !== false) {
        $key = [];
        foreach($inputIndexes as $iIndex){
            $key[] = trim($data[$iIndex]);
        }
        $key = implode(';', $key);
        if(!isset($combs[$key])){
            $combs[$key] = [0, 0, 0, 0]; // 0: num_combs, 1: profit, 2: trades, 3: winrate
        }
        $combs[$key][0]++;
        $combs[$key][1] += floatval($data[1]);
        $combs[$key][2] += intval($data[2]);
        $combs[$key][3] += floatval($data[7]);
    }
    fclose($handle);
}

$numCombinations = 1;
foreach($combs as $key => $comb){
    if($comb[0] > $numCombinations){
        $numCombinations = $comb[0];
    }
}


$sort = !empty($options['sort']) ? strval($options['sort']) : 'profit';
$minPairs = isset($options['min-pairs']) ? intval($options['min-pairs']) : false;
$minProfit = isset($options['min-profit']) ? floatval($options['min-profit']) : false;
$minTrades = isset($options['min-trades']) ? intval($options['min-trades']) : false;
$minWinrate = isset($options['min-winrate']) ? floatval($options['min-winrate']) : false;

// sum groups:
foreach($combs as $key => &$comb){
    $comb[1] = $comb[1] / $comb[0];
    $comb[3] = $comb[3] / $comb[0];

    if($minPairs != false && $comb[0] < $minPairs){
        unset($combs[$key]);
        continue;
    }

    if($minProfit != false && $comb[1] < $minProfit){
        unset($combs[$key]);
        continue;
    }

    if($minTrades != false && $comb[2] < $minTrades){
        unset($combs[$key]);
        continue;
    }

    if($minWinrate != false && $comb[3] < $minWinrate){
        unset($combs[$key]);
        continue;
    }
}

function compare_comb($a, $b) {
    global $sort_by;
    if($a[$sort_by] == $b[$sort_by]) {
        return 0;
    }
    return ($a[$sort_by] > $b[$sort_by]) ? -1 : 1;
};

$sort_by_options = [
    'profit' => 1,
    'trades' => 2,
    'winrate' => 3
];

$sort_by = isset($sort_by_options[$sort]) ? $sort_by_options[$sort] : 1;
uasort($combs, 'compare_comb');

$i = 0;
echo "Number of pairs: $numCombinations\n";
foreach($combs as $key => $comb){
    if($i > 10){
        break;
    }

    echo "$key: pairs: $comb[0], profit: $comb[1], trades: $comb[2], winrate: $comb[3]\n";
    $i++;
}

###########################################

