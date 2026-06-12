<?php
namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    private array $attributes = [

        [
            'name'          => 'اللون',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => true,
            'sort_order'    => 1,
            'options'       => [
                ['value' => 'أسود', 'color_code' => '#000000', 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => 'أبيض', 'color_code' => '#FFFFFF', 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => 'أحمر', 'color_code' => '#EF4444', 'sort_order' => 3, 'price_modifier' => 0.00],
                ['value' => 'أزرق', 'color_code' => '#3B82F6', 'sort_order' => 4, 'price_modifier' => 0.00],
                ['value' => 'كحلي', 'color_code' => '#1E3A5F', 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => 'أخضر', 'color_code' => '#22C55E', 'sort_order' => 6, 'price_modifier' => 0.00],
                ['value' => 'أصفر', 'color_code' => '#EAB308', 'sort_order' => 7, 'price_modifier' => 0.00],
                ['value' => 'برتقالي', 'color_code' => '#F97316', 'sort_order' => 8, 'price_modifier' => 0.00],
                ['value' => 'وردي', 'color_code' => '#EC4899', 'sort_order' => 9, 'price_modifier' => 0.00],
                ['value' => 'بنفسجي', 'color_code' => '#A855F7', 'sort_order' => 10, 'price_modifier' => 0.00],
                ['value' => 'رمادي', 'color_code' => '#6B7280', 'sort_order' => 11, 'price_modifier' => 0.00],
                ['value' => 'بيج', 'color_code' => '#D4B896', 'sort_order' => 12, 'price_modifier' => 0.00],
                ['value' => 'بني', 'color_code' => '#92400E', 'sort_order' => 13, 'price_modifier' => 0.00],
            ],
        ],

        [
            'name'          => 'مقاس الملابس',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => true,
            'sort_order'    => 2,
            'options'       => [
                ['value' => 'XS', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => 'S', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => 'M', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 0.00],
                ['value' => 'L', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 0.00],
                ['value' => 'XL', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => '2XL', 'color_code' => null, 'sort_order' => 6, 'price_modifier' => 2.00],
                ['value' => '3XL', 'color_code' => null, 'sort_order' => 7, 'price_modifier' => 2.00],
            ],
        ],

        [
            'name'          => 'مقاس البنطال',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => true,
            'sort_order'    => 3,
            'options'       => [
                ['value' => '28', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => '30', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => '32', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 0.00],
                ['value' => '34', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 0.00],
                ['value' => '36', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => '38', 'color_code' => null, 'sort_order' => 6, 'price_modifier' => 0.00],
                ['value' => '40', 'color_code' => null, 'sort_order' => 7, 'price_modifier' => 0.00],
                ['value' => '42', 'color_code' => null, 'sort_order' => 8, 'price_modifier' => 2.00],
                ['value' => '44', 'color_code' => null, 'sort_order' => 9, 'price_modifier' => 2.00],
            ],
        ],

        // 👟 UPDATED: Shoe sizes (now includes small sizes)
        [
            'name'          => 'مقاس الحذاء',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => true,
            'sort_order'    => 4,
            // 👟 UPDATED: Shoe sizes (now includes small sizes)
            'options'       => [
                ['value' => '21', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => '22', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => '23', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 0.00],
                ['value' => '24', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 0.00],
                ['value' => '25', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => '26', 'color_code' => null, 'sort_order' => 6, 'price_modifier' => 0.00],
                ['value' => '27', 'color_code' => null, 'sort_order' => 7, 'price_modifier' => 0.00],
                ['value' => '28', 'color_code' => null, 'sort_order' => 8, 'price_modifier' => 0.00],
                ['value' => '29', 'color_code' => null, 'sort_order' => 9, 'price_modifier' => 0.00],
                ['value' => '30', 'color_code' => null, 'sort_order' => 10, 'price_modifier' => 0.00],
                ['value' => '31', 'color_code' => null, 'sort_order' => 11, 'price_modifier' => 0.00],
                ['value' => '32', 'color_code' => null, 'sort_order' => 12, 'price_modifier' => 0.00],
                ['value' => '33', 'color_code' => null, 'sort_order' => 13, 'price_modifier' => 0.00],
                ['value' => '34', 'color_code' => null, 'sort_order' => 14, 'price_modifier' => 0.00],
                ['value' => '35', 'color_code' => null, 'sort_order' => 15, 'price_modifier' => 0.00],
                ['value' => '36', 'color_code' => null, 'sort_order' => 16, 'price_modifier' => 0.00],
                ['value' => '37', 'color_code' => null, 'sort_order' => 17, 'price_modifier' => 0.00],
                ['value' => '38', 'color_code' => null, 'sort_order' => 18, 'price_modifier' => 0.00],
                ['value' => '39', 'color_code' => null, 'sort_order' => 19, 'price_modifier' => 0.00],
                ['value' => '40', 'color_code' => null, 'sort_order' => 20, 'price_modifier' => 0.00],
                ['value' => '41', 'color_code' => null, 'sort_order' => 21, 'price_modifier' => 0.00],
                ['value' => '42', 'color_code' => null, 'sort_order' => 22, 'price_modifier' => 0.00],
                ['value' => '43', 'color_code' => null, 'sort_order' => 23, 'price_modifier' => 0.00],
                ['value' => '44', 'color_code' => null, 'sort_order' => 24, 'price_modifier' => 0.00],
                ['value' => '45', 'color_code' => null, 'sort_order' => 25, 'price_modifier' => 0.00],
                ['value' => '46', 'color_code' => null, 'sort_order' => 26, 'price_modifier' => 2.00],
            ],
        ],

        [
            'name'          => 'الخامة',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => false,
            'sort_order'    => 5,
            'options'       => [
                ['value' => 'قطن', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => 'بوليستر', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => 'كتان', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 5.00],
                ['value' => 'صوف', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 10.00],
                ['value' => 'جينز', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => 'جلد', 'color_code' => null, 'sort_order' => 6, 'price_modifier' => 20.00],
                ['value' => 'حرير', 'color_code' => null, 'sort_order' => 7, 'price_modifier' => 15.00],
                ['value' => 'فرو صناعي', 'color_code' => null, 'sort_order' => 8, 'price_modifier' => 0.00],
            ],
        ],


        [
            'name'          => 'السعة التخزينية',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => false,
            'sort_order'    => 6,
            'options'       => [
                ['value' => '64 جيجابايت', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => '128 جيجابايت', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 50.00],
                ['value' => '256 جيجابايت', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 100.00],
                ['value' => '512 جيجابايت', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 200.00],
                ['value' => '1 تيرابايت', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 350.00],
            ],
        ],

        [
            'name'          => 'فئة الوزن',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => false,
            'sort_order'    => 7,
            'options'       => [
                ['value' => 'خفيف', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => 'متوسط', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => 'ثقيل', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 0.00],
            ],
        ],

        // 👶 NEW: Kids age intervals
        [
            'name'          => 'الفئة العمرية للأطفال',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => false,
            'sort_order'    => 8,
            'options'       => [
                ['value' => 'حديث الولادة (0-3 أشهر)', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
                ['value' => 'رضع (3-12 شهر)', 'color_code' => null, 'sort_order' => 2, 'price_modifier' => 0.00],
                ['value' => '1-2 سنوات', 'color_code' => null, 'sort_order' => 3, 'price_modifier' => 0.00],
                ['value' => '3-4 سنوات', 'color_code' => null, 'sort_order' => 4, 'price_modifier' => 0.00],
                ['value' => '5-6 سنوات', 'color_code' => null, 'sort_order' => 5, 'price_modifier' => 0.00],
                ['value' => '7-8 سنوات', 'color_code' => null, 'sort_order' => 6, 'price_modifier' => 0.00],
                ['value' => '9-10 سنوات', 'color_code' => null, 'sort_order' => 7, 'price_modifier' => 0.00],
                ['value' => '11-12 سنة', 'color_code' => null, 'sort_order' => 8, 'price_modifier' => 0.00],
                ['value' => 'مراهق (13-15 سنة)', 'color_code' => null, 'sort_order' => 9, 'price_modifier' => 0.00],
            ],
        ],
                [
            'name'          => 'عام',
            'type'          => 'select',
            'is_filterable' => true,
            'is_required'   => false,
            'sort_order'    => 9,
            'options'       => [
                ['value' => 'عام', 'color_code' => null, 'sort_order' => 1, 'price_modifier' => 0.00],
            ],
        ],

    ];

    public function run(): void
    {
        foreach ($this->attributes as $attributeData) {

            $attribute = Attribute::updateOrCreate(
                ['slug' => Str::slug($attributeData['name'])],
                [
                    'name'          => $attributeData['name'],
                    'type'          => $attributeData['type'],
                    'is_filterable' => $attributeData['is_filterable'],
                    'is_required'   => $attributeData['is_required'],
                    'sort_order'    => $attributeData['sort_order'],
                ]
            );

            foreach ($attributeData['options'] as $optionData) {
                AttributeOption::updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value'        => $optionData['value'],
                    ],
                    [
                        'color_code'     => $optionData['color_code'],
                        'sort_order'     => $optionData['sort_order'],
                        'price_modifier' => $optionData['price_modifier'],
                    ]
                );
            }

        }
    }
}
