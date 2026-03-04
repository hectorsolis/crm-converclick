<?php
// FILE: app/Helpers/DateHelper.php
// Conversión de fechas entre UTC y timezone local

namespace App\Helpers;

use App\Models\Setting;
use DateTime;
use DateTimeZone;

class DateHelper
{
    public static function getTimezone(): string
    {
        return defined('TIMEZONE') ? TIMEZONE : 'America/Santiago';
    }

    /**
     * Convierte datetime UTC a timezone local para mostrar
     */
    public static function toLocal(?string $utcDatetime): string
    {
        if (empty($utcDatetime))
            return '';
        try {
            $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(self::getTimezone()));
            return $dt->format('d/m/Y H:i');
        }
        catch (\Exception $e) {
            return $utcDatetime;
        }
    }

    /**
     * Convierte datetime local a UTC para guardar
     */
    public static function toUtc(?string $localDatetime): ?string
    {
        if (empty($localDatetime))
            return null;
        try {
            $dt = new DateTime($localDatetime, new DateTimeZone(self::getTimezone()));
            $dt->setTimezone(new DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        }
        catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Fecha/hora local actual en formato MySQL
     */
    public static function nowLocal(): string
    {
        $dt = new DateTime('now', new DateTimeZone(self::getTimezone()));
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Retorna "hace X días" o "hoy" o "mañana" relativo
     */
    public static function relative(?string $utcDatetime): string
    {
        if (empty($utcDatetime))
            return '';
        try {
            $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $diff = $now->diff($dt);
            $days = (int)$diff->format('%R%a');

            if ($days === 0)
                return 'Hoy';
            if ($days === 1)
                return 'Mañana';
            if ($days === -1)
                return 'Ayer';
            if ($days > 1)
                return "En {$days} días";
            return "Hace " . abs($days) . " días";
        }
        catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Verifica si una fecha ya pasó (vencida)
     */
    public static function isOverdue(?string $utcDatetime): bool
    {
        if (empty($utcDatetime))
            return false;
        try {
            $dt = new DateTime($utcDatetime, new DateTimeZone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            return $dt < $now;
        }
        catch (\Exception $e) {
            return false;
        }
    }
}