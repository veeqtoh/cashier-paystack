<?php

declare(strict_types=1);

namespace Veeqtoh\Cashier\Concerns;

use Illuminate\Support\Collection;
use Veeqtoh\Cashier\Classes\Card;

trait ManagesCards
{
    /**
     * Get a collection of the entity's payment methods.
     */
    public function cards(): Collection
    {
        $cards                  = [];
        $paystackAuthorizations = $this->asPaystackCustomer()->authorizations;

        if (! is_null($paystackAuthorizations)) {
            foreach ($paystackAuthorizations as $card) {
                if ($card['channel'] == 'card')
                    $cards[] = new Card($this, $card);
            }
        }

        return new Collection($cards);
    }

    /**
     * Deletes the entity's payment methods.
     */
    public function deleteCards(): void
    {
        $this->cards()->each(function ($card) {
            $card->delete();
        });
    }
}