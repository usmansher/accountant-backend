<?php
namespace App\Helpers;

class AccountingHelper
{
    /**
     * Compare two values with the specified operator.
     *
     * @param float $value1
     * @param float $value2
     * @param string $operator
     * @return bool
     */
    public static function compare($value1, $value2, $operator)
    {
        switch ($operator) {
            case '==':
                return $value1 == $value2;
            case '!=':
                return $value1 != $value2;
            case '>':
                return $value1 > $value2;
            case '<':
                return $value1 < $value2;
            case '>=':
                return $value1 >= $value2;
            case '<=':
                return $value1 <= $value2;
            default:
                return false;
        }
    }

    /**
     * Convert a negative value to positive and vice versa.
     *
     * @param float $value
     * @return float
     */

    public static function convertToPositive($value)

    {
        return $value < 0 ? abs($value) : $value;
    }


    /**
     * Calculate the result of two values with the specified operator.
     *
     * @param float $value1
     * @param float $value2
     * @param string $operator
     * @return float
     */

    public static function calculate($value1, $value2, $operator)

    {
        switch ($operator) {
            case '+':
                return $value1 + $value2;
            case '-':
                return $value1 - $value2;
            case '*':
                return $value1 * $value2;
            case '/':
                return $value1 / $value2;
            default:
                return 0;
        }
    }

    /**
     * Calculate the result of two values with the specified operator and direction.
     *
     * @param float $amount1
     * @param string $dc1
     * @param float $amount2
     * @param string $dc2
     * @return array
     */

    public static function calculateWithDc($amount1, $dc1, $amount2, $dc2)
    {
        if ($dc1 == $dc2) {
            $amount = $amount1 + $amount2;
            $dc = $dc1;
        } else {
            if ($amount1 >= $amount2) {
                $amount = $amount1 - $amount2;
                $dc = $dc1;
            } else {
                $amount = $amount2 - $amount1;
                $dc = $dc2;
            }
        }

        return ['amount' => $amount, 'dc' => $dc];
    }

    public static function formatDate($date)
    {
        return date('Y-m-d', strtotime($date));
    }


    public static function formatDateTime($date)
    {
        return date('Y-m-d H:i:s', strtotime($date));
    }

    public static function toCurrency($value)
    {
        return $value;
    }

    public static function toCodeWithName($code, $name)
    {
        return $code . ' - ' . $name;
    }
}
