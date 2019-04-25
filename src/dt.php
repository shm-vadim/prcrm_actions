<?php

final class dt extends DateTime
{
    public static function isRealDbTime($time)
    {
        if ($time && self::createFromDBFormat($time)->setTimeZone(new DateTimeZone('UTC'))->getTimestamp() > 0) {
            return true;
        }
    }

    public static function createFromDBFormat($str)
    {
        $dt = self::createFromFormat('Y-m-d H:i:s', $str);

        return self::createFromTimestamp($dt->getTimeStamp());
    }

    public static function createFromTimestamp($time)
    {
        $dt = new self();

        return $dt->setTimestamp($time);
    }

    public function isPastOrNow()
    {
        return $this->getTimestamp() <= time();
    }

    public function __toString()
    {
        return $this->format('d.m.y в H.i');
    }

    public function date()
    {
        return $this->format('d.m.y');
    }
}
