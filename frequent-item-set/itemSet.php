<?php
/**
*/
class FrequentItemsets {
    protected function readFileInArray($inputFile) {
        $lines = array();
        $fileResource = fopen($inputFile,'r');
        while(!feof($fileResource)) {
            $lines[] = fgets($fileResource);
        }
        fclose($fileResource);
        
        return $lines;
    }
    
    protected function getUniqueItemsFromBasketsAndEachBasket(array $basketList) {
        $items = array();
        $baskets = array();
        foreach($basketList as $basket) {
            $basketItem = explode(',', $basket);
            $basketItem = array_map('trim', $basketItem); // Trim white spaces.
            $baskets[] = $basketItem; // Prepare individual basket.
            foreach($basketItem as $item) {
               // $item = trim($item);
                if(!in_array($item, $items)) {
                    $items[] = $item;
                }
            }
        }
        return array(
            'baskets' => $baskets,
            'uniqueItems' => $items
            );
    }
    
    protected function calculateItemFrequency($noOfBaskets, $basketList, $minItemFrequency, $minSupport) {
        $data = $this->getUniqueItemsFromBasketsAndEachBasket($basketList);
        $baskets = $data['baskets'];
        $items = $data['uniqueItems'];        
        $data['frequents'] = $this->matchItemFrequencyIntoBasket($items, $baskets, $minItemFrequency);
        return $data;
    }
    
    
    protected function matchItemFrequencyIntoBasket($items, $baskets, $n) {
        $chunks = $items;
        // Iterate of each chunks containing n items.
        $entryRecords = array();
        $chars = $items;
        $chunks = $this->getPowerSet($items);
        
        foreach($chunks as $entry) {
            if (empty($entry)) {
                continue;
            }
            if (count($entry) > 3 ) {
                continue;
            }            
            foreach ($baskets as $basket) {
              $intersect = array_intersect($entry, $basket);

              if ($entry == $intersect) {
                  sort($entry, SORT_NATURAL);
                  $key = implode(',', $entry);
                if (empty($entryRecords[$key])) {
                    $entryRecords[$key] = 1;
                } else {
                    $entryRecords[$key] += 1;
                }       
              }                
            }
        }
        return $entryRecords;
    }
    
    protected function getPowerSet($array) {
        // initialize by adding the empty set
        $results = array(array( ));
        foreach ($array as $element) {
            foreach ($results as $combination) {
                array_push($results, array_merge(array($element), $combination));
            }
        }
        return $results;
    }
    
    /**
     *
     */
    public function showItemFrequency($inputFile, $minItemFrequency =  3, $minSupport = 2) {
        $inputData = $this->readFileInArray($inputFile);
        // Read first entry as no of baskets and remaining will belong to baskets items.
        $noOfBaskets = array_shift($inputData);        
        if (count($inputData) === $noOfBaskets) {
            echo 'No of baskests do not matches with no of entries provided';
            exit;
        }        
        $data = $this->calculateItemFrequency($noOfBaskets, $inputData, $minItemFrequency, $minSupport);
        //print_R($data['uniqueItems']);
        echo 'Items: [', implode(', ', $data['uniqueItems']) , ']' , "\n";
        echo 'Number of baskets: ' . count($data['baskets']) . "\n";
        foreach($data['frequents']  as $itenary => $count) {
            echo "[$count => $itenary ]\n";
        }
    } 

}


// read file via command line.
$inputFile = $argv[1];

$frequentItemObj = new FrequentItemsets;
$frequentItemObj->showItemFrequency($inputFile);



