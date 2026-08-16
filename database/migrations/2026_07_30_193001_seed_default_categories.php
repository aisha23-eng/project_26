<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $categories = [
            ['name' => 'برمجيات',       'type' => 'expense', 'icon' => 'code',              'color' => '#2563eb', 'sort_order' => 1],
            ['name' => 'تشغيل',         'type' => 'expense', 'icon' => 'settings',          'color' => '#059669', 'sort_order' => 2],
            ['name' => 'وجبات',         'type' => 'expense', 'icon' => 'utensils-crossed',  'color' => '#d97706', 'sort_order' => 3],
            ['name' => 'سفر',           'type' => 'expense', 'icon' => 'plane',             'color' => '#7c3aed', 'sort_order' => 4],
            ['name' => 'إيجار',         'type' => 'expense', 'icon' => 'building',          'color' => '#dc2626', 'sort_order' => 5],
            ['name' => 'رواتب',         'type' => 'expense', 'icon' => 'users',             'color' => '#0891b2', 'sort_order' => 6],
            ['name' => 'تسويق',         'type' => 'expense', 'icon' => 'megaphone',         'color' => '#db2777', 'sort_order' => 7],
            ['name' => 'كهرباء وانترنت', 'type' => 'expense', 'icon' => 'zap',              'color' => '#ca8a04', 'sort_order' => 8],
            ['name' => 'صيانة',         'type' => 'expense', 'icon' => 'wrench',            'color' => '#65a30d', 'sort_order' => 9],
            ['name' => 'تأمين',         'type' => 'expense', 'icon' => 'shield',            'color' => '#4f46e5', 'sort_order' => 10],
            ['name' => 'استشارات',      'type' => 'expense', 'icon' => 'briefcase',         'color' => '#0d9488', 'sort_order' => 11],
            ['name' => 'أخرى',          'type' => 'expense', 'icon' => 'more-horizontal',   'color' => '#6b7280', 'sort_order' => 12],
            ['name' => 'مبيعات',        'type' => 'income',  'icon' => 'shopping-cart',     'color' => '#16a34a', 'sort_order' => 1],
            ['name' => 'خدمات',         'type' => 'income',  'icon' => 'handshake',         'color' => '#2563eb', 'sort_order' => 2],
            ['name' => 'استثمارات',     'type' => 'income',  'icon' => 'trending-up',       'color' => '#7c3aed', 'sort_order' => 3],
            ['name' => 'فري لانس',      'type' => 'income',  'icon' => 'laptop',            'color' => '#0891b2', 'sort_order' => 4],
            ['name' => 'دعم حكومي',     'type' => 'income',  'icon' => 'gift',              'color' => '#d97706', 'sort_order' => 5],
            ['name' => 'فوائد بنكية',   'type' => 'income',  'icon' => 'banknote',          'color' => '#059669', 'sort_order' => 6],
            ['name' => 'أخرى',          'type' => 'income',  'icon' => 'more-horizontal',   'color' => '#6b7280', 'sort_order' => 7],
        ];

        DB::table('categories')->insert(array_map(fn ($c) => $c + [
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $categories));
    }

    public function down(): void
    {
        DB::table('categories')->where('is_system', true)->delete();
    }
};
