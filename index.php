<?php

include 'Fixably.class.php';

// Task 1
// get token (we get token when we initialize the object)
$fixably = new Fixably();


// Task 2
// get order In descending order of status
var_dump($fixably->getOrders());

// Task 3
// List all orders with iPhone device and currently assigned to a technician
var_dump($fixably->searchDevice('devices', 'Apple', 'iPhone'));


// Task 4
// Total invoices
// Total invoiced amount

//This should list each unique week of November 2020.
// Each week should list the increase or decrease of the above values in percentage with a
//single decimal from the previous week.
$report = $fixably->getReport('2020-11-01', '2020-11-30');
if (is_array($report)) {
    var_dump($fixably->sortReport($report));
}


// Task 5
// Create a new order for a MacBook Pro and with the defect of Broken screen
$orderId = (int)$fixably->createOrder('Apple', 'MacBook Pro', 'Laptop');
var_dump($fixably->addNote($orderId, 'Defect of Broken screen'));