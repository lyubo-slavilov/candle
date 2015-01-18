<?php
namespace Service;

/**
 * Candle utilities
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Utils {

    /**
     * Gets an element from a array with default fallback
     *
     * @param array $from The array we are reading from
     * @param mixed $key The key of the element
     * @param mixed $default Default value which will be returned if the $from[$key] element is not presented
     * @return mixed
     */
    static public function getParam($from, $key, $default = null) {

        if (is_array($from)) {
            return isset($from[$key]) ? $from[$key] : $default;
        }
        if (is_object($from)) {
            return isset($from->$key) ? $from->$key : $default;
        }

        $type = gettype($from);
        trigger_error("First parameter must be an Array or an Object. {$type} given instead");

    }


    public function aparam(array $from, $key, $default = null)
    {
        return self::getParam($from, $key, $default);
    }

    public function oparam($object, $key, $default = null)
    {

        if (!is_object($object)) {
            return $default;
        }

        $stack = explode('.', $key);
        $value = $default;
        do {
            $k = array_shift($stack);
            if (isset($object->$k)) {
                $object = $object->$k;
            } else {
                $object = $default;
                break;
            }
        } while(count($stack) > 0);

        return $object;
    }

    public function timeNotationToSQL($notation)
    {
        preg_match('/now[ ]*([\-\+]?)[ ]*([0-9]+)([a-z])/i', $notation, $matches);

        if (count($matches) == 4) {
            $t = array(
                's' => 'SECOND',
                'm' => 'MINUTE',
                'h' => 'HOUR',
                'd' => 'DAY',
            );
            $unit = $t[$matches[3]];
            return "NOW() {$matches[1]} INTERVAL {$matches[2]} $unit";
        } else {
            return 'NOW()';
        }
    }

    private function intDiv($a, $b)
    {

        return (int) ($a - $a % $b) / $b;
    }

    public function plurify($value, $singularText, $pluralText)
    {
        $text = ($value > 1 || $value == 0) ? $pluralText : $singularText;
        return sprintf($text, $value);
    }

    public function hrDate($timestamp, $target = -1)
    {
        $target = $target == -1 ? time() : $target;
        $diff = $target - $timestamp;

        if (date('mY', $timestamp) == date('mY', $target)) {
            $dayThen = (int) date('d', $timestamp);
            $dayNow = (int) date('d', $target);
            $days = $dayNow - $dayThen;
            if ($days == 1) {
                return 'YD ' . date('H:i', $timestamp);
            } elseif ($days == 0) {
                return  date('H:i', $timestamp);
            }
        }

        return date('d.m.Y H:i', $timestamp);

    }
    public function hrTimeDiff($timestamp, $target = -1)
    {

        $target = $target == -1 ? time() : $target;

        $diff = $target - $timestamp;

        $days = $this->intDiv($diff , 86400);
        if ($days > 0) {
            $months = $this->intDiv($days , 30);

            if ($months > 0) {
                $years = $this->intDiv($days , 365);

                if ($years > 0) {
                    return $this->plurify($years, 'an year ago', '%s years ago');
                }

                return $this->plurify($months, 'a month ago', '%s months ago');
            }
        }

        $minutes = $this->intDiv($diff , 60);
        if ($minutes > 0) {
            $hours = $this->intDiv($minutes , 60);
            if ($hours > 0) {
                return $this->plurify($hours, 'an hour ago', '%s hours ago');
            }

            return $this->plurify($minutes, 'a minute ago', '%s minutes ago');
        }

        return 'just now';
    }
}