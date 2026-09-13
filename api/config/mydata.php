<?php

declare(strict_types=1);

return [
    /*
     * Το sandbox της ΑΑΔΕ. Η παραγωγή είναι https://mydatapi.aade.gr/myDATA
     * — δεν ορίζεται ως default επίτηδες: η εσφαλμένη διαβίβαση σε παραγωγικά
     * βιβλία δεν αναστρέφεται.
     */
    'base_url' => env('MYDATA_BASE_URL', 'https://mydataapidev.aade.gr'),

    'timeout' => (int) env('MYDATA_TIMEOUT', 30),
];
