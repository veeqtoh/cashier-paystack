<?php

use Illuminate\Database\Eloquent\Model;

test('models extends base model')
    ->expect('Veeqtoh\Cashier\Models')
    ->classes()
    ->toExtend(Model::class);
