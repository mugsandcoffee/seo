<?php

namespace MugsAndCoffee\SEO;

use \Exception;

/**
 * Generates page title which SEO compliant
 * @author Kenneth 'digiArtist_ph' P. Vallejos
 * @since Monday, December 18, 2017
 * @version 1.1.0
 *
 * Sample code:
 *
 *      $data = [
 *                  "data" => [
 *                         "name" => "Central Coast Realty Inc.",
 *                         "category" => "Realty",
 *                         "contact_no" => "(02) 235 852",
 *                         "address" => "New South Wales, Australia",
 *                           ],
 *                  "config" => [
 *                                 "priority" => [
 *                                                  "name,category,contact_no, address",
 *                                                  "name,category, contact_no",
 *                                                  "name,category",
 *                                                  "name,contact_no",
 *                                                  "name",
 *                                               ],
 *                               ],
 *              ];
 *
 *      new Rushmedia\SEO\PageTitle($data);
 *      echo Rushmedia\SEO\PageTitle::generate();
 *
 *      // $data = ['data' => ['name' => 'Central Coast Realty', 'category' => 'Realty', 'contact_no' => '(02) 235 852', 'address' => 'New South Wales, Australia'], 'config' => ['priority' => ['name,category,contact_no', 'name,category', 'name,contact_no']] ];
 *
 */
class PageTitle {

    protected static $_instances = [];
    protected $collection = [];
    protected $fields = [];
    protected $pageTitle = '';
    protected $config = [
        'data' => [],
        'config' => [
            'priority' => [],
            'max' => 70,
            'punctuation' => ['-', '|', ',', ',', ',', ',', ',', ',']
        ]
    ];

    public function __construct($params = [])
    {
        // merges multi-dimensional array recursively
        $this->collection = array_merge_recursive($this->config, $params);
        self::$_instances[] = $this;
    }

    public static function generate()
    {
        try {
            if (count(self::$_instances) < 1)
                throw new Exception('You haven\'t setup the pre-configuration settings');

            if (empty(self::$_instances[0]->collection['data']))
                throw new Exception('Your data field is empty. Please populate it to continue');

            foreach(self::$_instances as $instance) {
                $instance->process();
                return $instance->pageTitle;
            }

        } catch(Exception $e) {
            echo "Caugth exception: " . $e->getMessage() . "\n";
        }
    }

    private function process()
    {
        // process the fields appropriately
        foreach($this->collection['data'] as $key => $val) {
            array_push($this->fields, [
                'field' => $key,
                'text' => $val,
                'count' => $this->getCountChars($val)
            ]);
        }
        $this->prioritize();
    }

    private function getCountChars($chars)
    {
        return strlen($chars);
    }

    private function prioritize()
    {
        $priorities = $this->collection['config']['priority'];
        $max = $this->collection['config']['max'];
        $output = [];
        
        // loops thru the priority
        foreach($priorities as $prior) {

            // splits string into array
            $priorItems = preg_split('/,/', $prior);
            $charCount = 0;
            $tempStr = [];
            $cntr = 0;
            foreach($priorItems as $item) {

                // trims trailing slashes
                $item = trim($item);
                $itemInfo = $this->getItemValue($item);
                $charCount += intval($itemInfo['count']);

                // adds punctuations here
                $tempStr[] = $this->putPunctuation($itemInfo['text'], $cntr);
                // increments cntr
                $cntr++;
            }

            $output[$this->getActualCharCount($tempStr)] = implode(' ', $tempStr);
        }
        // checks the number of characters of the primary field
        if ($this->getPrimaryLength() >= $this->collection['config']['max']) {
            $this->pageTitle = $this->removeTrailingComma( $this->truncatePrimary($this->collection['data'][$this->getPrimaryText()]) );
        } else {
            krsort($output);

            foreach($output as $key => $val) {
                if ($key <= intval($this->collection['config']['max'])) {
                    $this->pageTitle = $this->removeTrailingComma( $val );
                    break;
                }
            }
            // call_debug($output);
        }
    }

    private function getMax($items)
    {
        if (empty($items))
            return 0;

        return max($items);
    }

    private function getMin($items)
    {
        if (empty($items))
            return 0;

        return min($items);
    }

    private function getPrimaryLength()
    {
        $primaryText = $this->collection['data'][$this->getPrimaryText()];

        if ($primaryText == '')
            return 0;

        return strlen($primaryText);
    }

    private function truncatePrimary($str)
    {
        $max = $this->collection['config']['max'];
        $strCount = strlen($str);
        return ($strCount > $max) ? substr($str, 0, $max) : $str;
    }

    private function removeTrailingComma($strChar)
    {
        $pattern = '/\s?,\s?$/';

        try {
            return preg_replace($pattern, '', $strChar);
        } catch(Exception $e) {
            return $e->getMessage();
        }
    }

    // get the primary text which is the field 'name'
    public function getPrimaryText()
    {
        $priorities = $this->collection['config']['priority'];
        $output = [];

        foreach($priorities as $prior) {
            $list = preg_split('/,/', $prior);
            $output[count($list)] = $prior;
        }

        return $this->getMin(($output));
    }

    private function getActualCharCount($hay)
    {
        $strChars = implode(' ', $hay);
        return strlen($strChars);
    }

    private function putPunctuation($text, $loop)
    {
        $punctuations = $this->config['config']['punctuation'];

        if ($loop > 0)
            return $punctuations[$loop - 1] . ' ' . $text;
        else
            return $text;
    }

    private function getItemValue($key)
    {
        $fields = $this->fields;

        foreach($fields as $row) {
            if ($row['field'] == $key) {
                return ['count' => $row['count'], 'text' => $row['text']];
            }
        }
    }
}
