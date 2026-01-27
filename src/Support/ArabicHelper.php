<?php

namespace Athka\Employees\Support;

class ArabicHelper
{
    /**
     * Reshapes Arabic text to be displayed correctly in PDF engines that don't support RTL/Joining.
     */
    public static function prepareForPdf($text)
    {
        if (empty($text)) return $text;
        
        // If no Arabic characters, return as is
        if (!preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        return self::reshape($text);
    }

    private static function reshape($text)
    {
        // Simple Arabic Reshaper Implementation
        $unshaped = self::utf8_to_array($text);
        $shaped = [];
        $count = count($unshaped);

        for ($i = 0; $i < $count; $i++) {
            $current = $unshaped[$i];
            $prev = ($i > 0) ? $unshaped[$i - 1] : null;
            $next = ($i < $count - 1) ? $unshaped[$i + 1] : null;

            $shaped[] = self::getGlyph($current, $prev, $next);
        }

        // Reverse the string for RTL
        return implode('', array_reverse($shaped));
    }

    private static function utf8_to_array($str)
    {
        return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    private static function getGlyph($char, $prev, $next)
    {
        $map = [
            'ا' => ['isolated' => 'ﺍ', 'initial' => 'ﺍ', 'medial' => 'ﺎ', 'final' => 'ﺎ', 'can_join_left' => false, 'can_join_right' => true],
            'أ' => ['isolated' => 'ﺃ', 'initial' => 'ﺃ', 'medial' => 'ﺄ', 'final' => 'ﺄ', 'can_join_left' => false, 'can_join_right' => true],
            'إ' => ['isolated' => 'ﺇ', 'initial' => 'ﺇ', 'medial' => 'ﺈ', 'final' => 'ﺈ', 'can_join_left' => false, 'can_join_right' => true],
            'آ' => ['isolated' => 'ﺁ', 'initial' => 'ﺁ', 'medial' => 'ﺂ', 'final' => 'ﺂ', 'can_join_left' => false, 'can_join_right' => true],
            'ب' => ['isolated' => 'ﺏ', 'initial' => 'ﺑ', 'medial' => 'ﺒ', 'final' => 'ﺐ', 'can_join_left' => true, 'can_join_right' => true],
            'ت' => ['isolated' => 'ﺕ', 'initial' => 'ﺗ', 'medial' => 'ﺘ', 'final' => 'ﺖ', 'can_join_left' => true, 'can_join_right' => true],
            'ث' => ['isolated' => 'ﺙ', 'initial' => 'ﺛ', 'medial' => 'ﺜ', 'final' => 'ﺚ', 'can_join_left' => true, 'can_join_right' => true],
            'ج' => ['isolated' => 'ﺝ', 'initial' => 'ﺟ', 'medial' => 'ﺠ', 'final' => 'ﺞ', 'can_join_left' => true, 'can_join_right' => true],
            'ح' => ['isolated' => 'ﺡ', 'initial' => 'ﺣ', 'medial' => 'ﺤ', 'final' => 'ﺢ', 'can_join_left' => true, 'can_join_right' => true],
            'خ' => ['isolated' => 'ﺥ', 'initial' => 'ﺧ', 'medial' => 'ﺨ', 'final' => 'ﺦ', 'can_join_left' => true, 'can_join_right' => true],
            'د' => ['isolated' => 'ﺩ', 'initial' => 'ﺩ', 'medial' => 'ﺪ', 'final' => 'ﺪ', 'can_join_left' => false, 'can_join_right' => true],
            'ذ' => ['isolated' => 'ﺫ', 'initial' => 'ﺫ', 'medial' => 'ﺬ', 'final' => 'ﺬ', 'can_join_left' => false, 'can_join_right' => true],
            'ر' => ['isolated' => 'ﺭ', 'initial' => 'ﺭ', 'medial' => 'ﺮ', 'final' => 'ﺮ', 'can_join_left' => false, 'can_join_right' => true],
            'ز' => ['isolated' => 'ﺯ', 'initial' => 'ﺯ', 'medial' => 'ﺰ', 'final' => 'ﺰ', 'can_join_left' => false, 'can_join_right' => true],
            'س' => ['isolated' => 'ﺱ', 'initial' => 'ﺳ', 'medial' => 'ﺴ', 'final' => 'ﺲ', 'can_join_left' => true, 'can_join_right' => true],
            'ش' => ['isolated' => 'ﺵ', 'initial' => 'ﺷ', 'medial' => 'ﺸ', 'final' => 'ﺶ', 'can_join_left' => true, 'can_join_right' => true],
            'ص' => ['isolated' => 'ﺹ', 'initial' => 'ﺻ', 'medial' => 'ﺼ', 'final' => 'ﺺ', 'can_join_left' => true, 'can_join_right' => true],
            'ض' => ['isolated' => 'ﺽ', 'initial' => 'ﺿ', 'medial' => 'ﻀ', 'final' => 'ﺾ', 'can_join_left' => true, 'can_join_right' => true],
            'ط' => ['isolated' => 'ﻁ', 'initial' => 'ﻃ', 'medial' => 'ﻄ', 'final' => 'ﻂ', 'can_join_left' => true, 'can_join_right' => true],
            'ظ' => ['isolated' => 'ﻅ', 'initial' => 'ﻇ', 'medial' => 'ﻈ', 'final' => 'ﻆ', 'can_join_left' => true, 'can_join_right' => true],
            'ع' => ['isolated' => 'ﻉ', 'initial' => 'ﻋ', 'medial' => 'ﻌ', 'final' => 'ﻊ', 'can_join_left' => true, 'can_join_right' => true],
            'غ' => ['isolated' => 'ﻍ', 'initial' => 'ﻏ', 'medial' => 'ﻐ', 'final' => 'ﻎ', 'can_join_left' => true, 'can_join_right' => true],
            'ف' => ['isolated' => 'ﻑ', 'initial' => 'ﻓ', 'medial' => 'ﻔ', 'final' => 'ﻒ', 'can_join_left' => true, 'can_join_right' => true],
            'ق' => ['isolated' => 'ﻕ', 'initial' => 'ﻗ', 'medial' => 'ﻘ', 'final' => 'ﻖ', 'can_join_left' => true, 'can_join_right' => true],
            'ك' => ['isolated' => 'ﻙ', 'initial' => 'ﻛ', 'medial' => 'ﻜ', 'final' => 'ﻚ', 'can_join_left' => true, 'can_join_right' => true],
            'ل' => ['isolated' => 'ﻝ', 'initial' => 'ﻟ', 'medial' => 'ﻠ', 'final' => 'ﻞ', 'can_join_left' => true, 'can_join_right' => true],
            'م' => ['isolated' => 'ﻡ', 'initial' => 'ﻣ', 'medial' => 'ﻤ', 'final' => 'ﻢ', 'can_join_left' => true, 'can_join_right' => true],
            'ن' => ['isolated' => 'ﻥ', 'initial' => 'ﻧ', 'medial' => 'ﻨ', 'final' => 'ﻦ', 'can_join_left' => true, 'can_join_right' => true],
            'ه' => ['isolated' => 'ﻩ', 'initial' => 'ﻫ', 'medial' => 'ﻬ', 'final' => 'ﻪ', 'can_join_left' => true, 'can_join_right' => true],
            'و' => ['isolated' => 'ﻭ', 'initial' => 'ﻭ', 'medial' => 'ﻮ', 'final' => 'ﻮ', 'can_join_left' => false, 'can_join_right' => true],
            'ي' => ['isolated' => 'ﻱ', 'initial' => 'ﻳ', 'medial' => 'ﻴ', 'final' => 'ﻲ', 'can_join_left' => true, 'can_join_right' => true],
            'ى' => ['isolated' => 'ﻯ', 'initial' => 'ﻯ', 'medial' => 'ﻰ', 'final' => 'ﻰ', 'can_join_left' => false, 'can_join_right' => true],
            'ة' => ['isolated' => 'ﺓ', 'initial' => 'ﺓ', 'medial' => 'ﺔ', 'final' => 'ﺔ', 'can_join_left' => false, 'can_join_right' => true],
            'ؤ' => ['isolated' => 'ﺅ', 'initial' => 'ﺅ', 'medial' => 'ﺆ', 'final' => 'ﺆ', 'can_join_left' => false, 'can_join_right' => true],
            'ئ' => ['isolated' => 'ﺋ', 'initial' => 'ﺋ', 'medial' => 'ﺌ', 'final' => 'ﺊ', 'can_join_left' => true, 'can_join_right' => true],
            'ء' => ['isolated' => 'ﺀ', 'initial' => 'ﺀ', 'medial' => 'ﺀ', 'final' => 'ﺀ', 'can_join_left' => false, 'can_join_right' => false],
            ' لا' => ['isolated' => 'ﻻ', 'initial' => 'ﻻ', 'medial' => 'ﻼ', 'final' => 'ﻼ', 'can_join_left' => false, 'can_join_right' => true],
        ];

        if (!isset($map[$char])) return $char;

        $can_join_prev = ($prev && isset($map[$prev]) && $map[$prev]['can_join_left']);
        $can_join_next = ($next && isset($map[$next]) && $map[$next]['can_join_right']);

        if ($can_join_prev && $can_join_next) return $map[$char]['medial'];
        if ($can_join_prev) return $map[$char]['final'];
        if ($can_join_next) return $map[$char]['initial'];
        
        return $map[$char]['isolated'];
    }
}




