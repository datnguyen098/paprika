<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaprikaCatalogSeeder extends Seeder
{
    private const EVENING_TIME = '18:00-00:30';

    public function run(): void
    {
        Dish::query()->delete();
        Category::dish()->delete();

        $categories = $this->seedCategories();
        $eveningSlot = $this->seedEveningSlot();

        foreach ($this->items() as $item) {
            [$number, $categorySlug, $viName, $enName, $elName, $price, $time] = $item;
            $category = $categories[$categorySlug];
            $slug = $this->slugFor($viName, $number);
            $description = $this->descriptionFor($number, 'vi', $viName, $category->name);

            $dish = Dish::create([
                'category_id' => $category->id,
                'name' => $viName,
                'slug' => $slug,
                'description' => $description,
                'content' => null,
                'ingredients' => null,
                'price' => $price,
                'sale_price' => null,
                'image' => $this->imageFor($number),
                'gallery' => null,
                'is_featured' => in_array($number, [1, 8, 12, 14, 20, 30, 40, 47, 49, 52], true),
                'is_active' => true,
                'sort_order' => $number,
                'meta_title' => $viName.' | Paprika',
                'meta_description' => $description,
                'meta_keywords' => null,
            ]);

            foreach ([
                'en' => [$enName, $this->descriptionFor($number, 'en', $enName, $categories[$categorySlug]->translations->firstWhere('locale', 'en')?->name ?? 'Paprika menu')],
                'el' => [$elName, $this->descriptionFor($number, 'el', $elName, $categories[$categorySlug]->translations->firstWhere('locale', 'el')?->name ?? 'μενού Paprika')],
            ] as $locale => [$name, $translatedDescription]) {
                $dish->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $translatedDescription,
                        'content' => null,
                        'ingredients' => null,
                        'meta_title' => $name.' | Paprika',
                        'meta_description' => $translatedDescription,
                        'meta_keywords' => null,
                    ],
                );
            }

            if ($eveningSlot && $time === self::EVENING_TIME) {
                $dish->timeSlots()->syncWithoutDetaching([$eveningSlot->id]);
            }
        }
    }

    private function seedCategories(): array
    {
        $categoryData = [
            'do-an-hy-lap' => [
                'vi' => ['Đồ ăn Hy Lạp', 'Các món Hy Lạp quen thuộc của Paprika: khai vị, salad, món nướng, pita và phần ăn chia sẻ.'],
                'en' => ['Greek Food', 'Paprika Greek dishes: starters, salads, grilled plates, pita wraps and sharing platters.'],
                'el' => ['Ελληνικό φαγητό', 'Ελληνικά πιάτα της Paprika: ορεκτικά, σαλάτες, ψητά, πίτες και μερίδες για παρέα.'],
                'image' => '/paprika/menu-catalog/item-001.jpg',
                'sort' => 1,
            ],
            'do-an-viet-nam' => [
                'vi' => ['Đồ ăn Việt Nam', 'Các món Việt Nam chủ đạo của Paprika: phở, bún, bánh mì, nem và đồ nướng.'],
                'en' => ['Vietnamese Food', 'Paprika Vietnamese dishes: pho, noodle bowls, banh mi, fried rolls and grilled skewers.'],
                'el' => ['Βιετναμέζικο φαγητό', 'Βιετναμέζικα πιάτα της Paprika: pho, μπολ με noodles, banh mi, τηγανητά ρολά και ψητά σουβλάκια.'],
                'image' => '/paprika/menu-catalog/item-040.jpg',
                'sort' => 2,
            ],
            'do-uong' => [
                'vi' => ['Đồ uống', 'Nước, nước ngọt, bia, rượu vang nhà làm và các đồ uống dùng kèm bữa ăn.'],
                'en' => ['Drinks', 'Water, soft drinks, beer, house wine and drinks to pair with your meal.'],
                'el' => ['Ποτά', 'Νερό, αναψυκτικά, μπίρες, κρασί παραγωγής μας και ποτά για το γεύμα σας.'],
                'image' => '/paprika/board.jpg',
                'sort' => 3,
            ],
        ];

        $categories = [];

        foreach ($categoryData as $slug => $data) {
            $category = Category::create([
                'name' => $data['vi'][0],
                'slug' => $slug,
                'description' => $data['vi'][1],
                'type' => 'dish',
                'image' => $data['image'],
                'sort_order' => $data['sort'],
                'is_active' => true,
                'meta_title' => $data['vi'][0].' | Paprika',
                'meta_description' => $data['vi'][1],
            ]);

            foreach (['en', 'el'] as $locale) {
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $data[$locale][0],
                        'slug' => $slug,
                        'description' => $data[$locale][1],
                        'meta_title' => $data[$locale][0].' | Paprika',
                        'meta_description' => $data[$locale][1],
                    ],
                );
            }

            $category->load('translations');
            $categories[$slug] = $category;
        }

        return $categories;
    }

    private function seedEveningSlot(): ?DishTimeSlot
    {
        $branch = Branch::query()->orderBy('sort_order')->orderBy('id')->first();

        if (! $branch) {
            return null;
        }

        $slot = DishTimeSlot::updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Buổi tối'],
            [
                'start_date' => null,
                'end_date' => null,
                'start_time' => '18:00',
                'end_time' => '00:30',
                'is_active' => true,
            ],
        );

        $slot->translations()->updateOrCreate(['locale' => 'en'], ['name' => 'Evening menu']);
        $slot->translations()->updateOrCreate(['locale' => 'el'], ['name' => 'Βραδινό μενού']);

        return $slot;
    }

    private function descriptionFor(int $number, string $locale, string $name, string $category): string
    {
        return $this->descriptions()[$number][$locale] ?? match ($locale) {
            'en' => "{$name} from the {$category} section, prepared fresh for the Paprika menu in Patras.",
            'el' => "{$name} από την ενότητα {$category}, με προσεγμένη παρασκευή για το μενού της Paprika στην Πάτρα.",
            default => "{$name} trong nhóm {$category}, được Paprika chuẩn bị tươi mới cho thực đơn tại Patras.",
        };
    }

    private function englishDescription(string $name, string $category): string
    {
        return "{$name} from the {$category} section, prepared for the updated Paprika menu in Patras.";
    }

    private function greekDescription(string $name, string $category): string
    {
        return "{$name} από την ενότητα {$category}, στο ανανεωμένο μενού της Paprika στην Πάτρα.";
    }

    private function descriptions(): array
    {
        return [
            1 => ['vi' => 'Bánh phô mai Skopelos giòn nhẹ, nhân phô mai béo thơm, hợp mở đầu bữa tối kiểu Hy Lạp.', 'en' => 'Crispy Skopelos cheese pie with a creamy cheese filling, ideal as a Greek evening starter.', 'el' => 'Τραγανή σκοπελίτικη τυρόπιτα με κρεμώδη γέμιση τυριού, ιδανική για βραδινό ορεκτικό.'],
            2 => ['vi' => 'Khoai tây cắt tươi chiên vàng, dùng nóng cùng món nướng, pita hoặc sốt chấm.', 'en' => 'Fresh-cut potatoes fried until golden, served hot with grilled plates, pita or dips.', 'el' => 'Φρέσκες πατάτες κομμένες στο χέρι, τηγανισμένες μέχρι να γίνουν χρυσαφένιες.'],
            3 => ['vi' => 'Chả bí ngòi thủ công chiên vàng, thơm rau gia vị, dùng kèm tzatziki mát.', 'en' => 'Handmade zucchini fritters with herbs, fried until golden and served with cool tzatziki.', 'el' => 'Χειροποίητοι κολοκυθοκεφτέδες με μυρωδικά, σερβιρισμένοι με δροσερό τζατζίκι.'],
            4 => ['vi' => 'Tzatziki làm từ sữa chua, dưa leo và tỏi, hợp ăn cùng pita, thịt nướng hoặc khoai chiên.', 'en' => 'Tzatziki made with yogurt, cucumber and garlic, perfect with pita, grilled meat or fries.', 'el' => 'Τζατζίκι με γιαούρτι, αγγούρι και σκόρδο, ιδανικό για πίτες, ψητά και πατάτες.'],
            5 => ['vi' => 'Sốt phô mai cay kiểu Hy Lạp, vị béo mặn và cay nhẹ, hợp chấm bánh mì hoặc món nướng.', 'en' => 'Spicy Greek cheese dip with a creamy, salty bite and gentle heat, good with bread or grilled dishes.', 'el' => 'Πικάντικη τυροκαυτερή με κρεμώδη υφή, για ψωμί, πίτες και ψητά.'],
            6 => ['vi' => 'Bánh phô mai mini 5 miếng, vỏ giòn, nhân phô mai mềm, dễ chia sẻ.', 'en' => 'Five mini cheese pies with a crisp shell and soft cheese filling, easy to share.', 'el' => 'Πέντε μικρά τυροπιτάκια με τραγανό φύλλο και μαλακή γέμιση τυριού.'],
            7 => ['vi' => 'Xúc xích làng nướng thơm khói, vị đậm, hợp dùng cùng salad và khoai chiên.', 'en' => 'Grilled village sausage with a smoky aroma and bold flavor, great with salad and fries.', 'el' => 'Χωριάτικο λουκάνικο σχάρας με καπνιστό άρωμα, ταιριάζει με σαλάτα και πατάτες.'],
            8 => ['vi' => 'Salad Hy Lạp với cà chua, dưa leo, olive và feta, vị tươi mát cho bữa ăn.', 'en' => 'Greek salad with tomato, cucumber, olives and feta, fresh and bright for the table.', 'el' => 'Χωριάτικη σαλάτα με ντομάτα, αγγούρι, ελιές και φέτα, δροσερή για το τραπέζι.'],
            9 => ['vi' => 'Dakos với bánh mì khô, cà chua, caper và phô mai katiki, vị chua nhẹ, tươi và no vừa.', 'en' => 'Dakos with rusk, tomato, capers and Katiki Domokou cheese, a fresh, lightly tangy starter.', 'el' => 'Ντάκος με παξιμάδι, ντομάτα, κάπαρη και κατίκι Δομοκού, φρέσκο και ελαφρά ξινό ορεκτικό.'],
            10 => ['vi' => 'Salad rau xanh với halloumi nướng, manouri và sốt balsamic, vị béo thanh.', 'en' => 'Green salad with grilled halloumi, manouri and balsamic vinaigrette, rich but refreshing.', 'el' => 'Πράσινη σαλάτα με χαλούμι σχάρας, μανούρι και βινεγκρέτ βαλσάμικου.'],
            11 => ['vi' => 'Salad Paprika với xà lách, bacon, phô mai, gà, crouton và sốt paprika.', 'en' => 'Paprika salad with lettuce, bacon, cheese, chicken, croutons and paprika sauce.', 'el' => 'Σαλάτα Paprika με μαρούλι, μπέικον, τυρί, κοτόπουλο, κρουτόν και σάλτσα πάπρικας.'],
            12 => ['vi' => 'Bò hầm sốt cà chua mềm, vị đậm và ấm, hợp ăn cùng bánh mì hoặc khoai.', 'en' => 'Tender beef braised in tomato sauce, warm and hearty with bread or potatoes.', 'el' => 'Μοσχαράκι σιγομαγειρεμένο σε σάλτσα ντομάτας, ζεστό και χορταστικό.'],
            13 => ['vi' => 'Gà trống hầm sốt cà chua kiểu gia đình Hy Lạp, thịt mềm và sốt thơm.', 'en' => 'Greek home-style rooster braised in tomato sauce until tender and aromatic.', 'el' => 'Κόκκορας κοκκινιστός σε σπιτική σάλτσα ντομάτας, μαλακός και αρωματικός.'],
            14 => ['vi' => 'Gà nướng kiểu païdakia, phần thịt mọng, thơm than và gia vị.', 'en' => 'Grilled chicken païdakia with juicy meat, charcoal aroma and simple seasoning.', 'el' => 'Κοτόπουλο παϊδάκια στη σχάρα, ζουμερό με άρωμα κάρβουνου και απλά μυρωδικά.'],
            15 => ['vi' => 'Sườn cốt lết heo nướng đậm vị, phần thịt chắc, hợp dùng cùng salad và khoai.', 'en' => 'Grilled pork chop with a rich savory flavor, great with salad and fries.', 'el' => 'Χοιρινή μπριζόλα σχάρας με γεμάτη γεύση, ιδανική με σαλάτα και πατάτες.'],
            16 => ['vi' => 'Phi lê gà nướng mềm, vị nhẹ, phù hợp cho bữa ăn gọn mà đủ chất.', 'en' => 'Tender grilled chicken fillet, light and balanced for a simple meal.', 'el' => 'Τρυφερό φιλέτο κοτόπουλο στη σχάρα, ελαφρύ και ισορροπημένο.'],
            17 => ['vi' => 'Bò băm nướng mọng, nêm vừa miệng, dùng như món chính cùng đồ ăn kèm.', 'en' => 'Juicy beef patty, well seasoned and served as a main with sides.', 'el' => 'Ζουμερό μοσχαρίσιο μπιφτέκι με σωστό καρύκευμα, σερβίρεται ως κυρίως.'],
            18 => ['vi' => 'Ba chỉ heo nướng thơm khói, phần mỡ giòn nhẹ và thịt đậm vị.', 'en' => 'Grilled pork belly with smoky aroma, lightly crisp fat and savory meat.', 'el' => 'Πανσέτα σχάρας με καπνιστό άρωμα, τραγανό λίπος και πλούσια γεύση.'],
            19 => ['vi' => 'Sườn cừu nướng kiểu Hy Lạp, thơm béo, hợp cho bữa tối.', 'en' => 'Greek-style grilled lamb chops, rich and aromatic for dinner.', 'el' => 'Αρνίσια παιδάκια στη σχάρα, πλούσια και αρωματικά για βραδινό.'],
            20 => ['vi' => 'Xiên souvlaki heo nướng nhanh, vị quen thuộc, dễ gọi thêm hoặc cuốn pita.', 'en' => 'Pork souvlaki skewer, quick, familiar and easy to add to pita or sides.', 'el' => 'Σουβλάκι χοιρινό στη σχάρα, κλασική επιλογή για πίτα ή συνοδευτικό.'],
            21 => ['vi' => 'Xiên souvlaki gà nướng mềm, gọn vị, phù hợp ăn nhanh hoặc gọi kèm.', 'en' => 'Chicken souvlaki skewer, tender and simple for a quick bite or extra side.', 'el' => 'Σουβλάκι κοτόπουλο, τρυφερό και απλό, για γρήγορο γεύμα ή έξτρα μερίδα.'],
            22 => ['vi' => 'Kontosouvli gà nướng chậm, thịt mềm và thơm gia vị.', 'en' => 'Slow-grilled chicken kontosouvli with tender meat and warm spices.', 'el' => 'Κοντοσούβλι κοτόπουλο αργά ψημένο, με τρυφερό κρέας και αρώματα.'],
            23 => ['vi' => 'Kontosouvli heo nướng chậm, vị đậm, hợp dùng trong bữa tối.', 'en' => 'Slow-grilled pork kontosouvli with bold flavor, best for the evening menu.', 'el' => 'Κοντοσούβλι χοιρινό αργά ψημένο, με γεμάτη γεύση για το βραδινό μενού.'],
            24 => ['vi' => 'Đùi gà quay xiên, da thơm, thịt mềm, dùng nóng cùng đồ ăn kèm.', 'en' => 'Rotisserie chicken leg with aromatic skin and soft meat, served hot with sides.', 'el' => 'Μπούτι κοτόπουλο σούβλας με αρωματική πέτσα και μαλακό κρέας.'],
            25 => ['vi' => 'Cánh gà đậm vị, dễ chia sẻ trong nhóm hoặc gọi thêm cùng đồ uống.', 'en' => 'Flavorful chicken wings, easy to share at the table or order with drinks.', 'el' => 'Φτερούγες κοτόπουλο με γεμάτη γεύση, ιδανικές για παρέα.'],
            26 => ['vi' => 'Bánh mì phần nhỏ ăn kèm sốt, salad hoặc các món hầm.', 'en' => 'Small bread portion to pair with dips, salads or braised dishes.', 'el' => 'Μερίδα ψωμιού για συνοδεία με ντιπ, σαλάτες ή μαγειρευτά.'],
            27 => ['vi' => 'Bánh bột nhỏ dùng kèm món chính, phù hợp gọi thêm cho bàn.', 'en' => 'Small dough bites served as an extra side for main dishes.', 'el' => 'Μικρά ζυμαράκια ως έξτρα συνοδευτικό για τα κυρίως πιάτα.'],
            28 => ['vi' => 'Mâm nướng 2 người với nhiều phần thịt và món kèm, hợp chia sẻ.', 'en' => 'Sharing platter for two with grilled meats and sides.', 'el' => 'Ποικιλία για δύο άτομα με ψητά κρέατα και συνοδευτικά.'],
            29 => ['vi' => 'Mâm nướng 4 người nhiều món hơn, phù hợp nhóm bạn hoặc gia đình.', 'en' => 'Larger sharing platter for four, made for friends or family.', 'el' => 'Μεγάλη ποικιλία για τέσσερα άτομα, ιδανική για παρέα ή οικογένεια.'],
            30 => ['vi' => 'Pita cuốn souvlaki heo với rau, khoai và sốt, tiện cho bữa nhanh.', 'en' => 'Pita wrap with pork souvlaki, vegetables, fries and sauce for a quick meal.', 'el' => 'Πίτα με χοιρινό σουβλάκι, λαχανικά, πατάτες και σάλτσα για γρήγορο γεύμα.'],
            31 => ['vi' => 'Pita cuốn souvlaki gà với rau, khoai và sốt, vị nhẹ dễ ăn.', 'en' => 'Pita wrap with chicken souvlaki, vegetables, fries and sauce, light and easy.', 'el' => 'Πίτα με σουβλάκι κοτόπουλο, λαχανικά, πατάτες και σάλτσα, ελαφριά και εύκολη.'],
            32 => ['vi' => 'Pita với kebab nướng, rau và sốt, đậm vị kiểu Hy Lạp.', 'en' => 'Pita with grilled kebab, vegetables and sauce, bold Greek flavor.', 'el' => 'Πίτα με κεμπάπ σχάρας, λαχανικά και σάλτσα, με έντονη ελληνική γεύση.'],
            33 => ['vi' => 'Pita với xúc xích nướng, khoai, rau và sốt, no gọn.', 'en' => 'Pita with grilled sausage, fries, vegetables and sauce, compact and filling.', 'el' => 'Πίτα με λουκάνικο σχάρας, πατάτες, λαχανικά και σάλτσα.'],
            34 => ['vi' => 'Pita với bò băm nướng, rau và sốt, vị đậm hơn pita thường.', 'en' => 'Pita with beef patty, vegetables and sauce, richer than a classic pita.', 'el' => 'Πίτα με μοσχαρίσιο μπιφτέκι, λαχανικά και σάλτσα, πιο πλούσια επιλογή.'],
            35 => ['vi' => 'Pita với kontosouvli heo, thịt nướng chậm thơm và nhiều vị.', 'en' => 'Pita with pork kontosouvli, slow-grilled meat and full flavor.', 'el' => 'Πίτα με κοντοσούβλι χοιρινό, αργά ψημένο και γεμάτο γεύση.'],
            36 => ['vi' => 'Pita với kontosouvli gà, thịt mềm, sốt và rau tươi.', 'en' => 'Pita with chicken kontosouvli, tender meat, sauce and fresh vegetables.', 'el' => 'Πίτα με κοντοσούβλι κοτόπουλο, τρυφερό κρέας, σάλτσα και φρέσκα λαχανικά.'],
            37 => ['vi' => 'Pita phủ với bò băm nướng, phần lớn hơn, nhiều nhân và sốt.', 'en' => 'Covered pita with beef patty, a larger portion with more filling and sauce.', 'el' => 'Σκεπαστή πίτα με μοσχαρίσιο μπιφτέκι, μεγαλύτερη μερίδα με περισσότερη γέμιση.'],
            38 => ['vi' => 'Pita phủ với thăn heo nướng, no bụng, hợp bữa chính.', 'en' => 'Covered pita with pork steak, filling enough for a full meal.', 'el' => 'Σκεπαστή πίτα με χοιρινό μπριζολάκι, χορταστική για κυρίως γεύμα.'],
            39 => ['vi' => 'Pita phủ với phi lê gà, vị nhẹ, phần ăn gọn và đủ no.', 'en' => 'Covered pita with chicken fillet, lighter but still satisfying.', 'el' => 'Σκεπαστή πίτα με φιλέτο κοτόπουλο, πιο ελαφριά αλλά χορταστική.'],
            40 => ['vi' => 'Phở bò với nước dùng thơm, bánh phở mềm và thịt bò thái mỏng.', 'en' => 'Beef pho with fragrant broth, soft rice noodles and thinly sliced beef.', 'el' => 'Pho με μοσχάρι, αρωματικό ζωμό, μαλακά noodles ρυζιού και λεπτές φέτες κρέατος.'],
            41 => ['vi' => 'Phở gà thanh nhẹ, nước dùng ấm, thịt gà mềm và rau thơm.', 'en' => 'Light chicken pho with warm broth, tender chicken and fresh herbs.', 'el' => 'Pho με κοτόπουλο, ελαφρύ ζεστό ζωμό, τρυφερό κοτόπουλο και φρέσκα μυρωδικά.'],
            42 => ['vi' => 'Phở cuốn tươi với rau, thịt và nước chấm, món nhẹ dễ ăn.', 'en' => 'Fresh pho rolls with herbs, meat and dipping sauce, light and easy to eat.', 'el' => 'Φρέσκα ρολά pho με μυρωδικά, κρέας και σάλτσα για βούτηγμα.'],
            43 => ['vi' => 'Xiên thịt lợn nướng kiểu Việt, ướp đậm, thơm than.', 'en' => 'Vietnamese-style grilled pork skewer, well marinated and smoky.', 'el' => 'Βιετναμέζικο χοιρινό σουβλάκι, καλά μαριναρισμένο και ψημένο στη σχάρα.'],
            44 => ['vi' => 'Xiên thịt gà nướng kiểu Việt, mềm, thơm gia vị và dễ gọi kèm.', 'en' => 'Vietnamese-style grilled chicken skewer, tender, aromatic and easy to pair.', 'el' => 'Βιετναμέζικο σουβλάκι κοτόπουλο, τρυφερό, αρωματικό και εύκολο για συνοδεία.'],
            45 => ['vi' => 'Cánh gà chiên kiểu KFC, vỏ giòn, thịt mọng, hợp ăn vặt.', 'en' => 'KFC-style fried chicken wings with a crisp coating and juicy meat.', 'el' => 'Τηγανητές φτερούγες κοτόπουλου τύπου KFC, τραγανές έξω και ζουμερές μέσα.'],
            46 => ['vi' => 'Bún trộn thịt nướng với rau tươi, bún mềm và nước mắm pha.', 'en' => 'Grilled pork noodle bowl with fresh vegetables, soft noodles and fish sauce dressing.', 'el' => 'Μπολ με φιδέ, ψητό χοιρινό, φρέσκα λαχανικά και βιετναμέζικη σάλτσα.'],
            47 => ['vi' => 'Nem rán Việt Nam giòn rụm, nhân thịt rau củ, dùng kèm nước chấm.', 'en' => 'Crispy Vietnamese fried spring rolls filled with meat and vegetables, served with dipping sauce.', 'el' => 'Τραγανά βιετναμέζικα τηγανητά ρολά με γέμιση κρέατος και λαχανικών.'],
            48 => ['vi' => 'Mực nhồi thịt nướng, vị biển rõ, nhân đậm đà và thơm than.', 'en' => 'Grilled stuffed squid with clear seafood flavor, savory filling and charcoal aroma.', 'el' => 'Ψητό γεμιστό καλαμάρι με θαλασσινή γεύση, πλούσια γέμιση και άρωμα σχάρας.'],
            49 => ['vi' => 'Bánh mì Việt Nam với thịt nướng, đồ chua, rau thơm và sốt.', 'en' => 'Vietnamese banh mi with grilled pork, pickles, herbs and sauce.', 'el' => 'Βιετναμέζικο banh mi με ψητό χοιρινό, πίκλες, μυρωδικά και σάλτσα.'],
            50 => ['vi' => 'Bánh mì pate và thịt nguội kiểu Việt, béo thơm, chua ngọt cân bằng.', 'en' => 'Vietnamese banh mi with pate and cold cuts, rich, aromatic and balanced with pickles.', 'el' => 'Banh mi με πατέ και αλλαντικά, αρωματικό και ισορροπημένο με πίκλες.'],
            51 => ['vi' => 'Tôm xiên nướng thơm, thịt ngọt, hợp dùng như món chính hoặc gọi kèm.', 'en' => 'Grilled shrimp skewers, sweet and aromatic, good as a main or side.', 'el' => 'Ψητά σουβλάκια γαρίδας, γλυκά και αρωματικά, για κυρίως ή συνοδευτικό.'],
            52 => ['vi' => 'Phở hải sản với nước dùng nóng, topping hải sản và rau thơm.', 'en' => 'Seafood pho with hot broth, seafood toppings and fresh herbs.', 'el' => 'Pho με θαλασσινά, ζεστό ζωμό, θαλασσινά και φρέσκα μυρωδικά.'],
            53 => ['vi' => 'Mì xào hải sản, sợi mì săn, rau củ và sốt xào đậm vị.', 'en' => 'Seafood stir-fried noodles with springy noodles, vegetables and savory sauce.', 'el' => 'Τηγανητά noodles με θαλασσινά, λαχανικά και γευστική σάλτσα.'],
            54 => ['vi' => 'Mì xào bò với thịt bò mềm, rau củ giòn và sốt thơm.', 'en' => 'Beef stir-fried noodles with tender beef, crisp vegetables and aromatic sauce.', 'el' => 'Τηγανητά noodles με μοσχάρι, τραγανά λαχανικά και αρωματική σάλτσα.'],
            55 => ['vi' => 'Chả tôm chiên giòn, vị tôm rõ, ăn kèm sốt chấm.', 'en' => 'Crispy shrimp cakes with clear shrimp flavor, served with dipping sauce.', 'el' => 'Τραγανοί γαριδοκεφτέδες με καθαρή γεύση γαρίδας και σάλτσα.'],
            56 => ['vi' => 'Bánh bao nhân thịt hấp nóng, vỏ mềm và nhân đậm đà.', 'en' => 'Warm steamed pork bao with a soft bun and savory filling.', 'el' => 'Ζεστό bao ατμού με χοιρινό, μαλακό ψωμάκι και πλούσια γέμιση.'],
            57 => ['vi' => 'Xá xíu thịt lợn nướng, vị mặn ngọt, thơm sốt barbecue kiểu Việt.', 'en' => 'Vietnamese char siu barbecue pork with a sweet-savory glaze.', 'el' => 'Βιετναμέζικο char siu χοιρινό με γλυκόαλμη γλάσο και άρωμα barbecue.'],
            58 => ['vi' => 'Chả bò viên chiên nóng, vỏ giòn nhẹ, hợp ăn vặt hoặc gọi kèm.', 'en' => 'Fried beef croquettes, hot and lightly crisp, good as a snack or side.', 'el' => 'Τηγανητές μοσχαρίσιες κροκέτες, ζεστές και ελαφρά τραγανές.'],
            59 => ['vi' => 'Chả cá viên chiên dai giòn, dễ ăn cùng sốt chấm.', 'en' => 'Fried fish croquettes with a chewy-crisp texture, easy with dipping sauce.', 'el' => 'Τηγανητές ψαροκροκέτες με ελαφρά τραγανή υφή και σάλτσα.'],
            60 => ['vi' => 'Nước suối chai lớn 1.5 lít, phù hợp dùng chung cho bàn.', 'en' => 'Large 1.5 l bottle of still water, good for sharing at the table.', 'el' => 'Μεγάλο μπουκάλι νερό 1,5 λίτρου, κατάλληλο για το τραπέζι.'],
            61 => ['vi' => 'Nước suối chai nhỏ 0.5 lít, tiện mang đi hoặc dùng kèm bữa.', 'en' => 'Small 0.5 l bottle of still water, convenient for takeaway or a meal.', 'el' => 'Μικρό μπουκάλι νερό 0,5 λίτρου, πρακτικό για φαγητό ή πακέτο.'],
            62 => ['vi' => 'Nước có ga mát lạnh, hợp dùng cùng món nướng và món chiên.', 'en' => 'Chilled sparkling water, great with grilled or fried dishes.', 'el' => 'Δροσερό ανθρακούχο νερό, ιδανικό με ψητά ή τηγανητά πιάτα.'],
            63 => ['vi' => 'Nước ngọt lon 330 ml, lựa chọn nhanh cho bữa ăn.', 'en' => '330 ml soft drink can, a quick drink choice for your meal.', 'el' => 'Αναψυκτικό 330 ml, γρήγορη επιλογή για το γεύμα σας.'],
            64 => ['vi' => 'Coca-Cola Zero 330 ml, vị cola không đường, dùng lạnh.', 'en' => '330 ml Coca-Cola Zero, chilled and sugar-free.', 'el' => 'Coca-Cola Zero 330 ml, δροσερή και χωρίς ζάχαρη.'],
            65 => ['vi' => 'Nước ngọt chai 500 ml, phần lớn hơn cho bữa ăn dài hơn.', 'en' => '500 ml soft drink bottle for a longer meal.', 'el' => 'Αναψυκτικό 500 ml, μεγαλύτερη επιλογή για μεγαλύτερο γεύμα.'],
            66 => ['vi' => 'Coca-Cola Zero 500 ml, không đường, dùng lạnh.', 'en' => '500 ml Coca-Cola Zero, chilled and sugar-free.', 'el' => 'Coca-Cola Zero 500 ml, δροσερή και χωρίς ζάχαρη.'],
            67 => ['vi' => 'Bia chai/lon gồm Mamos, Heineken, Alfa và Alfa không cồn.', 'en' => 'Beer selection including Mamos, Heineken, Alfa and alcohol-free Alfa.', 'el' => 'Επιλογή μπίρας με Mamos, Heineken, Alfa και Alfa χωρίς αλκοόλ.'],
            68 => ['vi' => 'Rượu vang nhà làm 0.5 kg, có trắng, đỏ hoặc hồng.', 'en' => 'House wine 0.5 kg, available in white, red or rose.', 'el' => 'Κρασί παραγωγής μας 0,5 kg, λευκό, κόκκινο ή ροζέ.'],
            69 => ['vi' => 'Bình nhỏ ouzo hoặc tsipouro 100 ml, dùng kiểu Hy Lạp.', 'en' => 'Small 100 ml carafe of ouzo or tsipouro, served Greek-style.', 'el' => 'Μικρό καραφάκι 100 ml με ούζο ή τσίπουρο, όπως σερβίρεται στην Ελλάδα.'],
            70 => ['vi' => 'Ly rượu vang, ouzo hoặc tsipouro cho một phần uống nhanh.', 'en' => 'Glass of wine, ouzo or tsipouro for a quick single serving.', 'el' => 'Ποτήρι κρασί, ούζο ή τσίπουρο για μία γρήγορη μερίδα.'],
            71 => ['vi' => 'Fanta cam xanh 330 ml, dùng lạnh.', 'en' => '330 ml Fanta blue orangeade, served chilled.', 'el' => 'Fanta πορτοκαλάδα μπλε 330 ml, σερβίρεται δροσερή.'],
            72 => ['vi' => 'Fanta chanh 330 ml, vị chanh tươi mát.', 'en' => '330 ml Fanta lemonade with a fresh lemon taste.', 'el' => 'Fanta λεμονάδα 330 ml με δροσερή γεύση λεμονιού.'],
            73 => ['vi' => 'Fanta cam có ga 330 ml, vị cam sảng khoái.', 'en' => '330 ml carbonated orange Fanta, bright and refreshing.', 'el' => 'Fanta πορτοκαλάδα με ανθρακικό 330 ml, δροσερή και αναζωογονητική.'],
            74 => ['vi' => 'Sprite/Gazoz lon 330 ml, vị chanh nhẹ, có ga.', 'en' => '330 ml Sprite/Gazoz can with light lemon flavor and bubbles.', 'el' => 'Sprite/Gazoz 330 ml με ελαφριά γεύση λεμονιού και ανθρακικό.'],
            75 => ['vi' => 'Tuborg soda 330 ml, nước soda có ga dùng lạnh.', 'en' => '330 ml Tuborg soda, chilled and sparkling.', 'el' => 'Tuborg σόδα 330 ml, δροσερή και ανθρακούχα.'],
        ];
    }

    private function imageFor(int $number): string
    {
        $path = sprintf('/paprika/menu-catalog/item-%03d.jpg', $number);

        return file_exists(public_path(ltrim($path, '/'))) ? $path : '/paprika/board.jpg';
    }

    private function slugFor(string $name, int $number): string
    {
        return match ($number) {
            40 => 'pho-bo',
            41 => 'pho-ga',
            42 => 'pho-cuon',
            43 => 'thit-lon-xien-nuong',
            44 => 'thit-ga-xien-nuong',
            45 => 'canh-ga-kfc',
            46 => 'bun-tron-thit-nuong',
            47 => 'nem-ran',
            48 => 'muc-nhoi-thit-nuong',
            49 => 'banh-my-thit-nuong',
            50 => 'banh-my-pate-thit-nguoi',
            51 => 'tom-xien-nuong',
            52 => 'pho-hai-san',
            53 => 'my-xao-hai-san',
            54 => 'my-xao-thit-bo',
            55 => 'cha-tom-chien-gion',
            56 => 'banh-bao-nhan-thit',
            57 => 'xa-xiu',
            58 => 'cha-bo-vien-chien',
            59 => 'cha-ca-vien-chien',
            default => Str::slug($name) ?: 'mon-'.$number,
        };
    }

    private function items(): array
    {
        return [
            [1, 'do-an-hy-lap', 'Bánh phô mai Skopelos', 'Skopelos cheese pie', 'Σκοπελίτικη τυρόπιτα', 600, self::EVENING_TIME],
            [2, 'do-an-hy-lap', 'Khoai tây chiên tươi', 'Fresh fried potatoes', 'Φρέσκες πατάτες τηγανιτές', 400, null],
            [3, 'do-an-hy-lap', 'Chả bí ngòi thủ công ăn kèm tzatziki', 'Handmade zucchini fritters with tzatziki', 'Κολοκυθοκεφτέδες χειροποίητοι (συνοδεύονται με τζατζίκι)', 600, null],
            [4, 'do-an-hy-lap', 'Sốt tzatziki thủ công', 'Handmade tzatziki', 'Τζατζίκι χειροποίητο', 450, self::EVENING_TIME],
            [5, 'do-an-hy-lap', 'Sốt phô mai cay tirokafteri', 'Handmade spicy cheese dip', 'Τυροκαυτερή χειροποίητη', 450, self::EVENING_TIME],
            [6, 'do-an-hy-lap', 'Bánh phô mai mini 5 miếng', 'Mini cheese pies 5 pcs', 'Τυροπιτάκια 5τμχ', 500, self::EVENING_TIME],
            [7, 'do-an-hy-lap', 'Xúc xích làng nướng', 'Grilled village sausage', 'Λουκάνικο χωριάτικο σχάρας', 700, self::EVENING_TIME],
            [8, 'do-an-hy-lap', 'Salad Hy Lạp', 'Greek salad', 'Χωριάτικη', 850, self::EVENING_TIME],
            [9, 'do-an-hy-lap', 'Dakos với cà chua, caper và phô mai katiki', 'Dakos with tomato, capers and Katiki Domokou cheese', 'Ντάκος (παξιμάδι, ντομάτα, κάπαρι, κατίκι Δομοκού)', 800, self::EVENING_TIME],
            [10, 'do-an-hy-lap', 'Salad xanh với phô mai', 'Green salad with cheeses', 'Πράσινη με τυριά', 900, self::EVENING_TIME],
            [11, 'do-an-hy-lap', 'Salad Paprika', 'Paprika salad', 'Πάπρικα', 900, self::EVENING_TIME],
            [12, 'do-an-hy-lap', 'Bò hầm sốt cà chua', 'Braised beef in tomato sauce', 'Μοσχαράκι κοκκινιστό', 1150, null],
            [13, 'do-an-hy-lap', 'Gà trống hầm sốt cà chua', 'Braised rooster in tomato sauce', 'Κόκκορας κοκκινιστός', 1150, null],
            [14, 'do-an-hy-lap', 'Gà nướng kiểu païdakia', 'Grilled chicken païdakia', 'Κοτόπουλο παϊδάκια σχάρας', 1100, self::EVENING_TIME],
            [15, 'do-an-hy-lap', 'Sườn cốt lết heo', 'Pork chop', 'Χοιρινή μπριζόλα', 1000, self::EVENING_TIME],
            [16, 'do-an-hy-lap', 'Ức gà phi lê', 'Chicken fillet', 'Φιλέτο κοτόπουλο', 900, self::EVENING_TIME],
            [17, 'do-an-hy-lap', 'Bò băm nướng', 'Beef burger patty', 'Μπιφτέκι μοσχαρίσιο', 1000, self::EVENING_TIME],
            [18, 'do-an-hy-lap', 'Ba chỉ heo nướng', 'Grilled pork belly', 'Πανσέτα σχάρας', 850, self::EVENING_TIME],
            [19, 'do-an-hy-lap', 'Sườn cừu nướng', 'Lamb chops', 'Παιδάκια αρνίσια', 1400, self::EVENING_TIME],
            [20, 'do-an-hy-lap', 'Souvlaki heo', 'Pork souvlaki', 'Σουβλάκι χοιρινό', 230, null],
            [21, 'do-an-hy-lap', 'Souvlaki gà', 'Chicken souvlaki', 'Σουβλάκι κοτόπουλο', 230, null],
            [22, 'do-an-hy-lap', 'Kontosouvli gà', 'Chicken kontosouvli', 'Κοντοσούβλι κοτόπουλο', 1000, self::EVENING_TIME],
            [23, 'do-an-hy-lap', 'Kontosouvli heo', 'Pork kontosouvli', 'Κοντοσούβλι χοιρινό', 1000, self::EVENING_TIME],
            [24, 'do-an-hy-lap', 'Đùi gà quay xiên', 'Rotisserie chicken leg', 'Μπούτι κοτόπουλο σούβλας', 800, self::EVENING_TIME],
            [25, 'do-an-hy-lap', 'Cánh gà', 'Chicken wings', 'Φτερούγες κοτόπουλο', 750, self::EVENING_TIME],
            [26, 'do-an-hy-lap', 'Bánh mì theo phần', 'Bread per person', 'Ψωμί κατ’ άτομο', 100, null],
            [27, 'do-an-hy-lap', 'Bánh bột nhỏ từng miếng', 'Small dough bites', 'Ζυμαράκια τμχ.', 80, null],
            [28, 'do-an-hy-lap', 'Mâm nướng 2 người', 'Sharing platter for 2', '2 ατόμων', 2500, self::EVENING_TIME],
            [29, 'do-an-hy-lap', 'Mâm nướng 4 người', 'Sharing platter for 4', '4 ατόμων', 4000, self::EVENING_TIME],
            [30, 'do-an-hy-lap', 'Pita với souvlaki heo', 'Pita with pork souvlaki', 'Πίτα με σουβλάκι χοιρινό', 400, null],
            [31, 'do-an-hy-lap', 'Pita với souvlaki gà', 'Pita with chicken souvlaki', 'Πίτα με σουβλάκι κοτόπουλο', 400, null],
            [32, 'do-an-hy-lap', 'Pita với kebab', 'Pita with kebab skewer', 'Πίτα με σουβλάκι κεμπάπ', 400, null],
            [33, 'do-an-hy-lap', 'Pita với xúc xích', 'Pita with sausage skewer', 'Πίτα με σουβλάκι λουκάνικο', 400, null],
            [34, 'do-an-hy-lap', 'Pita với bò băm nướng', 'Pita with beef burger patty', 'Πίτα με μπιφτέκι μοσχαρίσιο', 450, null],
            [35, 'do-an-hy-lap', 'Pita với kontosouvli heo', 'Pita with pork kontosouvli', 'Πίτα με κοντοσούβλι χοιρινό', 450, null],
            [36, 'do-an-hy-lap', 'Pita với kontosouvli gà', 'Pita with chicken kontosouvli', 'Πίτα με κοντοσούβλι κοτόπουλο', 450, null],
            [37, 'do-an-hy-lap', 'Pita phủ với bò băm nướng', 'Covered pita with beef burger patty', 'Πίτα σκεπαστή με μπιφτέκι μοσχ', 800, null],
            [38, 'do-an-hy-lap', 'Pita phủ với thăn heo', 'Covered pita with pork steak', 'Πίτα σκεπαστή με μπριζολάκι χοιρινό', 800, null],
            [39, 'do-an-hy-lap', 'Pita phủ với phi lê gà', 'Covered pita with chicken fillet', 'Πίτα σκεπαστή με φιλέτο κοτόπουλο', 800, null],
            [40, 'do-an-viet-nam', 'Phở bò', 'Beef pho', 'Μοσχαρίσιο Φο', 950, null],
            [41, 'do-an-viet-nam', 'Phở gà', 'Chicken pho', 'Κοτόπουλο με Φο', 800, null],
            [42, 'do-an-viet-nam', 'Phở cuốn tươi', 'Fresh pho rolls', 'Φρέσκα Ρολά Φο', 550, null],
            [43, 'do-an-viet-nam', 'Thịt lợn xiên nướng', 'Grilled pork skewer', 'Ψητό Σουβλάκι Χοιρινό', 230, null],
            [44, 'do-an-viet-nam', 'Thịt gà xiên nướng', 'Grilled chicken skewer', 'Ψητό Σουβλάκι Κοτόπουλο', 230, null],
            [45, 'do-an-viet-nam', 'Cánh gà chiên kiểu KFC', 'KFC-style fried chicken wings', 'Τηγανητές Φτερούγες Κοτόπουλου στυλ KFC', 580, null],
            [46, 'do-an-viet-nam', 'Bún trộn thịt nướng', 'Grilled pork noodle bowl', 'Μπολ με Φιδέ και Ψητό Χοιρινό', 800, null],
            [47, 'do-an-viet-nam', 'Nem rán Việt Nam', 'Vietnamese fried spring rolls', 'Βιετναμέζικα Τηγανητά Σπρινγκ Ρολς', 550, null],
            [48, 'do-an-viet-nam', 'Mực nhồi thịt nướng', 'Grilled stuffed squid', 'Ψητό Γεμιστό Καλαμάρι', 950, null],
            [49, 'do-an-viet-nam', 'Bánh mì thịt nướng', 'Vietnamese banh mi with grilled pork', 'Βιετναμέζικο Μπαν Μι με Ψητό Χοιρινό', 580, null],
            [50, 'do-an-viet-nam', 'Bánh mì pate và thịt nguội', 'Vietnamese banh mi with pate and cold cuts', 'Βιετναμέζικο Μπαν Μι με Πατέ & Αλλαντικά', 580, null],
            [51, 'do-an-viet-nam', 'Tôm xiên nướng', 'Grilled shrimp skewers', 'Ψητά Σουβλάκια Γαρίδας', 950, null],
            [52, 'do-an-viet-nam', 'Phở hải sản', 'Seafood pho', 'Φο με Θαλασσινά', 990, null],
            [53, 'do-an-viet-nam', 'Mì xào hải sản', 'Seafood stir-fried noodles', 'Τηγανητά νουντλς με Θαλασσινά', 950, null],
            [54, 'do-an-viet-nam', 'Mì xào thịt bò', 'Beef stir-fried noodles', 'Τηγανητά νουντλς με μοσχάρι', 850, null],
            [55, 'do-an-viet-nam', 'Chả tôm chiên giòn', 'Crispy shrimp cakes', 'Τραγανές Τηγανητές Γαριδοκεφτέδες', 580, null],
            [56, 'do-an-viet-nam', 'Bánh bao nhân thịt', 'Steamed pork bao bun', 'Ψωμάκι Ατμού με Χοιρινό - Μπάο Μπαν', 620, null],
            [57, 'do-an-viet-nam', 'Xá xíu thịt lợn nướng', 'Vietnamese char siu barbecue pork', 'Βιετναμέζικο Τσαρ Σίου / Μπάρμπεκιου Χοιρινό', 520, null],
            [58, 'do-an-viet-nam', 'Chả bò viên chiên', 'Fried beef croquettes', 'Τηγανητές Μοσχαρίσιες Κροκέτες', 580, null],
            [59, 'do-an-viet-nam', 'Chả cá viên chiên', 'Fried fish croquettes', 'Τηγανητές Ψαροκροκέτες', 580, null],
            [60, 'do-uong', 'Nước 1.5 lít', 'Water 1.5 l', 'Νερό (1.5 lt)', 150, null],
            [61, 'do-uong', 'Nước 0.5 lít', 'Water 0.5 l', 'Νερό (0,5 lt)', 50, null],
            [62, 'do-uong', 'Nước có ga', 'Sparkling water', 'Ανθρακούχο νερό', 200, null],
            [63, 'do-uong', 'Nước ngọt 330 ml', 'Soft drinks 330 ml', 'Αναψυκτικά (330 ml)', 200, null],
            [64, 'do-uong', 'Coca-Cola Zero 330 ml', 'Coca-Cola Zero 330 ml', 'Coca-Cola Zero 330ml', 200, null],
            [65, 'do-uong', 'Nước ngọt 500 ml', 'Soft drinks 500 ml', 'Αναψυκτικά (500ml)', 270, null],
            [66, 'do-uong', 'Coca-Cola Zero 500 ml', 'Coca-Cola Zero 500 ml', 'Coca-Cola Zero 500ml', 270, null],
            [67, 'do-uong', 'Bia', 'Beers', 'Μπύρες', 500, null],
            [68, 'do-uong', 'Rượu vang nhà làm 0.5 kg', 'House wine 0.5 kg', 'Κρασί παραγωγής μας 0,5 Kg', 500, null],
            [69, 'do-uong', 'Bình ouzo hoặc tsipouro 100 ml', 'Ouzo or tsipouro carafe 100 ml', 'Καραφάκι Ούζο - Τσίπουρο 100 ml', 500, null],
            [70, 'do-uong', 'Ly rượu vang, ouzo hoặc tsipouro', 'Glass of wine, ouzo or tsipouro', 'Ποτήρι κρασί-ούζο-τσίπουρο', 200, null],
            [71, 'do-uong', 'Fanta cam xanh 330 ml', 'Fanta blue orangeade 330 ml', 'FANTA Πορτοκαλάδα Μπλε 330ml', 200, null],
            [72, 'do-uong', 'Fanta chanh 330 ml', 'Fanta lemonade 330 ml', 'Fanta Λεμονάδα 330ml', 200, null],
            [73, 'do-uong', 'Fanta cam có ga 330 ml', 'Fanta carbonated orange 330 ml', 'Fanta Πορτοκαλάδα με Ανθρακικό 330ml', 200, null],
            [74, 'do-uong', 'Sprite gazoz lon 330 ml', 'Sprite gazoz can 330 ml', 'SPRITE | Αναψυκτικό Γκαζόζα Κουτί 330ml', 200, null],
            [75, 'do-uong', 'Tuborg soda 330 ml', 'Tuborg soda 330 ml', 'TUBORG Σόδα 330ml', 200, null],
        ];
    }
}
