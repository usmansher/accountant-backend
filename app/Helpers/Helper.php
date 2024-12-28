<?php

use Illuminate\Support\Facades\File;

function envu($data = array())
{
    foreach ($data as $key => $value) {
        if (env($key) === $value) {
            unset($data[$key]);
        }
    }

    if (!count($data)) {
        return false;
    }

    // write only if there is change in content

    $env = file_get_contents(base_path() . '/.env');
    $env = explode("\n", $env);
    foreach ((array)$data as $key => $value) {
        foreach ($env as $env_key => $env_value) {
            $entry = explode("=", $env_value, 2);
            if ($entry[0] === $key) {
                $env[$env_key] = $key . "=" . (is_string($value) ? '"'.$value.'"' : $value);
            } else {
                $env[$env_key] = $env_value;
            }
        }
    }
    $env = implode("\n", $env);
    file_put_contents(base_path() . '/.env', $env);
    return true;
}

//////////////////////////////////////////////////////////////////////// Date helper function starts

/*
 *  Used to check whether date is valid or not
 *  @param
 *  $date as timestamp or date variable
 *  @return true if valid date, else if not
 */

function validateDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/*
 *  Used to get date with start midnight time
 *  @param
 *  $date as timestamp or date variable
 *  @return date with start midnight time
 */

function getStartOfDate($date)
{
    return date('Y-m-d', strtotime($date)).' 00:00';
}

/*
 *  Used to get date with end midnight time
 *  @param
 *  $date as timestamp or date variable
 *  @return date with end midnight time
 */

function getEndOfDate($date)
{
    return date('Y-m-d', strtotime($date)).' 23:59';
}

/*
 *  Used to get date in desired format
 *  @return date format
 */

function getDateFormat()
{
    if (config('config.date_format') === 'DD-MM-YYYY') {
        return 'd-m-Y';
    } elseif (config('config.date_format') === 'MM-DD-YYYY') {
        return 'm-d-Y';
    } elseif (config('config.date_format') === 'DD-MMM-YYYY') {
        return 'd-M-Y';
    } elseif (config('config.date_format') === 'MMM-DD-YYYY') {
        return 'M-d-Y';
    } else {
        return 'd-m-Y';
    }
}

/*
 *  Used to convert date for database
 *  @param
 *  $date as date
 *  @return date
 */

function toDate($date)
{
    if (!$date) {
        return;
    }

    return date('Y-m-d', strtotime($date));
}

/*
 *  Used to convert date in desired format
 *  @param
 *  $date as date
 *  @return date
 */

function showDate($date)
{
    if (!$date) {
        return;
    }

    $date_format = getDateFormat();
    return date($date_format, strtotime($date));
}

/*
 *  Used to convert time in desired format
 *  @param
 *  $datetime as datetime
 *  @return datetime
 */

function showDateTime($time = '')
{
    if (!$time) {
        return;
    }

    $date_format = getDateFormat();
    if (config('config.time_format') === 'H:mm') {
        return date($date_format.',H:i', strtotime($time));
    } else {
        return date($date_format.',h:i a', strtotime($time));
    }
}

/*
 *  Used to convert time in desired format
 *  @param
 *  $time as time
 *  @return time
 */

function showTime($time = '')
{
    if (!$time) {
        return;
    }

    if (config('config.time_format') === 'H:mm') {
        return date('H:i', strtotime($time));
    } else {
        return date('h:i a', strtotime($time));
    }
}
function toWord($word)
{
    $word = str_replace('_', ' ', $word);
    $word = str_replace('-', ' ', $word);
    $word = ucwords($word);
    return $word;
}


function scriptStripper($string)
{
    return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $string);
}


function generateSelectOption($data)
{
    $options = array();
    foreach ($data as $key => $value) {
        $options[] = ['name' => $value, 'id' => $key];
    }
    return $options;
}


function generateTranslatedSelectOption($data)
{
    $options = array();
    foreach ($data as $key => $value) {
        $options[] = ['name' => trans('list.'.$value), 'id' => $value];
    }
    return $options;
}


function generateNormalSelectOption($data)
{
    $options = array();
    foreach ($data as $key => $value) {
        $options[] = ['text' => $value, 'value' => $key];
    }
    return $options;
}

function generateNormalSelectOption2($data)
{
    $options = array();
    foreach ($data as $key => $value) {
        $options[] = ['label' => $value, 'value' => $key];
    }
    return $options;
}


function generateNormalSelectOptionValueOnly($data)
{
    $options = array();
    foreach ($data as $value) {
        $options[] = ['text' => $value, 'value' => $value];
    }
    return $options;
}

function formatNumber($number, $decimal_place = 2)
{
    return round($number, $decimal_place);
}


/*
 *  Used to get value-list json
 *  @return array
 */

function getVar($list)
{
    $file = resource_path('var/'.$list.'.json');
    return (File::exists($file)) ? json_decode(file_get_contents($file), true) : [];
}

// Get translation
if (!function_exists('__choice')) {
    function __choice($key, array $replace = [], $number = null)
    {
        return trans_choice($key, $number, $replace);
    }
}

// Format Decimal
if (!function_exists('formatDecimal')) {
    function formatDecimal($number, $decimals = 4, $ds = '.', $ts = '')
    {
        return number_format($number, $decimals, $ds, $ts);
    }
}

