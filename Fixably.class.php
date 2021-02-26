<?php

class Fixably
{

    const BASEURL = 'https://careers-api.fixably.com/';
    const EMAIL = 'matti.meikalainen@fixably.com';
    const NAME = 'Matti Meikäläinen';

    const GET_TOKEN_URL = self::BASEURL . 'token/';
    const CREATE_ORDER_URL = self::BASEURL . 'orders/create/';
    const CREATE_NOTE_URL = self::BASEURL . 'orders/{id}/notes/create/';
    const GET_ORDER_URL = self::BASEURL . 'orders/';
    const SEARCH_URL = self::BASEURL . 'search/{type}/';
    const REPORT_URL = self::BASEURL . 'report/{from}/{to}/';


    private $_token = null;

    public function __construct()
    {

        $data = [
            'Email' => self::EMAIL,
            'Name' => self::NAME,
        ];
        try {
            $token = $this->fetchAPI(self::GET_TOKEN_URL, 'POST', $data);
            if (isset($token['token'])) {
                $this->_token = $token['token'];
            } else {
//                die($token['error']);
            }
        } catch(Exception $e) {

        }
    }


    /**
     * @param string $url
     * @param string $method
     * @param array $data
     * @return mixed
     */
    private function fetchAPI(string $url, string $method = 'GET', array $data=[])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $headers = array('Content-Type: multipart/form-data');

        switch (strtolower($method))
        {
            case 'post':
                curl_setopt($curl, CURLOPT_POST, true);

                if ($this->_token) {
                    $headers[] = "X-Fixably-Token: $this->_token";
                }

                if (count($data)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            default:
                $headers[] = "X-Fixably-Token: $this->_token";

                if (count($data)) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        curl_close($curl);
        if (isset ($result['error'])) {
//            die($result['error']);
        }
        return json_decode($result, true);
    }



    public function sortOrderByStatusInDescending(array $orders)
    {
        usort($orders, function($a, $b) {
            return $b['status'] - $a['status'];
        });

        return $orders;
    }

    /**
     * Get orders
     * @return mixed
     */
    public function getOrders()
    {
        $getOrders = $this->fetchAPI(self::GET_ORDER_URL, 'GET');
        return $getOrders['results'];
    }


    /**
     * Create orders
     * @param string $manufacturer
     * @param string $brand
     * @param string $type
     * @return string
     */
    public function createOrder(string $manufacturer, string $brand, string $type)
    {
        $createData = [
            'DeviceManufacturer' => $manufacturer,
            'DeviceBrand' => $brand,
            'DeviceType' => $type,
        ];
        $create = $this->fetchAPI(self::CREATE_ORDER_URL, 'POST', $createData);

        return $createData['id'];
    }

    /**
     * Add a note to a order
     * @param int $orderId
     * @param string $note
     */
    public function addNote(int $orderId, string $note)
    {
        $noteData = [
            'Description' => $note,
            'Type' => 'Issue'
        ];
        $url = str_replace('{id}', $orderId, self::CREATE_NOTE_URL);
        $note = $this->fetchAPI($url, 'POST', $noteData);
    }


    /**
     * @param string $searchType
     * @param string $criteria
     * @param string $searchText
     */
    public function searchDevice(string $searchType, string $criteria, string $searchText)
    {
        $searchData = [
            'Criteria' => $criteria,
        ];
        $url = str_replace('{type}', $searchType, self::SEARCH_URL);
        $searchData = $this->fetchAPI($url, 'POST', $searchData);

        $searchResults = json_encode($searchData['results'], true);

        $onlyIphone = array_filter(array_map(function($order) use ($searchText) {
            $conditionCheckTechnician = $order['technician'] !== null;
            $conditionCheckDeviceBrand = strpos(strtolower($order['deviceBrand']), $searchText) !== false;

            return ($conditionCheckTechnician && $conditionCheckDeviceBrand) ? $order : array();
        }, $searchResults));
    }


    /**
     * Get a report
     * @param string $fromDate
     * @param string $toDate
     * @return mixed
     */
    public function getReport(string $fromDate, string $toDate)
    {
        $reportData = [
            'page' => 1,
        ];

        $url = str_replace(array('{from}', '{to}'), array($fromDate, $toDate), self::REPORT_URL);
        $reportData = $this->fetchAPI($url, 'POST', $reportData);

        return $reportData['results'];
    }


    /**
     *  list each unique week of report with invoice infomation
     * @param array $reports
     * @return array
     * @throws Exception
     */
    public function sortReport(array $reports)
    {

        // order invoice based on week number
        $weekNumber = [];
        foreach($reports as $order) {
            $date = new DateTime($order['created']);
            $week = $date->format("W");
            $weekNumber[$week][] = $order;
        }

        // get Total invoice and Invoice sum per week
        $weekInvoice = [];
        foreach ($weekNumber as $key => $week) {
            $invoice = 0;
            $invoiceSum = 0;
            foreach($weekNumber[$key] as $a) {
                $invoice++;
                $invoiceSum += $a['amount'];
            }

            $weekInvoice[$key]['invoicetotal'] = $invoice;
            $weekInvoice[$key]['invoiceSum'] = $invoiceSum;
        }

        // get data  compare to previous Week
        $wkNumber = null;
        foreach($weekInvoice as $key=>$w) {
            if ($wkNumber === null) {
                $status = $key;
                $weekInvoice[$key]['status invoiceTotal'] = 0;
                $weekInvoice[$key]['status invoiceSum'] = 0;
            } else {
                $weekInvoice[$key]['invoiceTotal: compare to previous Week'] = $weekInvoice[$key]['invoiceTotal'] - $weekInvoice[$wkNumber]['invoiceTotal'];
                $weekInvoice[$key]['invoiceSum: compare to previous Week'] = $weekInvoice[$key]['invoiceSum'] - $weekInvoice[$wkNumber]['invoiceSum'];
            }
        }


        return $weekInvoice;
    }
}