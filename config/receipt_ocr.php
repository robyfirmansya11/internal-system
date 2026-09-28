<?php

return [
    /*
     * OCR is intentionally opt-in. It only runs after Tesseract has been
     * installed on the server and RECEIPT_OCR_ENABLED is set to true.
     */
    'enabled' => env('RECEIPT_OCR_ENABLED', false),
    'binary' => env('TESSERACT_BINARY', 'tesseract'),
    'languages' => env('TESSERACT_LANGUAGES', 'eng'),
    'page_segmentation_mode' => (int) env('TESSERACT_PSM', 6),
    'timeout' => (int) env('TESSERACT_TIMEOUT', 30),
];
