<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Utils;

use GeoPagos\ApiBundle\Services\PDF\PDFConverter;

class EchoMockedPdfConverter extends PDFConverter
{
    public function __construct()
    {
    }

    public function htmlToPdf($html, $queryParams = [], $timeout = 7)
    {
        return $html;
    }
}
