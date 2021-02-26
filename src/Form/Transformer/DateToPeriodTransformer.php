<?php

namespace GeoPagos\WithholdingTaxBundle\Form\Transformer;

use Carbon\Carbon;
use Symfony\Component\Form\DataTransformerInterface;

class DateToPeriodTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        return Carbon::instance($value)
            ->tz('UTC')
            ->startOfMonth()
            ->startOfDay()
            ->toDateTimeString();
    }
}
