<?php

test('providers extend the base provider class')
    ->expect('Veeqtoh\CashierPaystack\Providers')
    ->classes()
    ->toExtend(\Illuminate\Support\ServiceProvider::class);