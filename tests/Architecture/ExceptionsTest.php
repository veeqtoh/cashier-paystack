<?php

test('exceptions extend the base exception class')
    ->expect('Veeqtoh\Cashier\Exceptions')
    ->classes()
    ->toExtend(Exception::class);