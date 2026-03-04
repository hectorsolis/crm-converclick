<?php
// FILE: app/Helpers/PhoneNormalizer.php
// Normaliza números de teléfono para deduplicación

namespace App\Helpers;

class PhoneNormalizer
{
    /**
     * Normaliza un teléfono: quita espacios, +, guiones, paréntesis.
     * Conserva el código de país si estaba presente.
     * Ejemplo: "+56 9 8765 4321" => "56987654321"
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone))
            return null;

        // Quitar todo excepto dígitos (conservamos el número completo con código país)
        $normalized = preg_replace('/[^\d]/', '', $phone);

        // Evitar strings vacíos
        if (empty($normalized))
            return null;

        // Si empieza con país implícito de Chile (9 dígitos empezando en 9)
        // lo dejamos como está — no asumimos código de país
        return $normalized;
    }

    /**
     * Formatea para mostrar en UI (formato chileno)
     * "56998765432" => "+56 9 9876 5432"
     */
    public static function format(?string $phone): string
    {
        if (empty($phone))
            return '';

        $n = preg_replace('/[^\d]/', '', $phone);
        $len = strlen($n);

        // Con código de país 56
        if ($len === 11 && str_starts_with($n, '56')) {
            return '+56 ' . substr($n, 2, 1) . ' ' . substr($n, 3, 4) . ' ' . substr($n, 7);
        }

        // 9 dígitos (Chile sin código país)
        if ($len === 9 && str_starts_with($n, '9')) {
            return '+56 ' . substr($n, 0, 1) . ' ' . substr($n, 1, 4) . ' ' . substr($n, 5);
        }

        // Retornar tal cual si no coincide patrón conocido
        return $phone;
    }
}