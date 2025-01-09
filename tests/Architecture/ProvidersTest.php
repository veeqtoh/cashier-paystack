<?php

test('providers extend the base provider class')
    ->expect('Veeqtoh\Cashier\Providers')
    ->classes()
    ->toExtend(\Illuminate\Support\ServiceProvider::class);