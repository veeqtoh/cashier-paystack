<?php

test('factories extend the base factory class')
    ->expect('Veeqtoh\Cashier\Database\Factories')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Factories\Factory');