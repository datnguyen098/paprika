import 'package:flutter/widgets.dart';

class UiText {
  UiText._(this._code);

  final String _code;

  static UiText of(BuildContext context) {
    return UiText._(Localizations.localeOf(context).languageCode);
  }

  String pick({
    required String vi,
    required String en,
    required String el,
  }) {
    return switch (_code) {
      'en' => en,
      'el' => el,
      _ => vi,
    };
  }

  String get blog => pick(vi: 'Blog', en: 'Blog', el: 'Blog');
  String get gallery => pick(vi: 'Không gian', en: 'Space', el: 'Χώρος');
  String get pages => pick(vi: 'Thông tin', en: 'Info', el: 'Πληροφορίες');
  String get vouchers => pick(vi: 'Ưu đãi', en: 'Offers', el: 'Προσφορές');
  String get allergens => pick(vi: 'Dị ứng', en: 'Allergens', el: 'Αλλεργιογόνα');
  String get retry => pick(vi: 'Thử lại', en: 'Retry', el: 'Δοκιμή ξανά');
  String get close => pick(vi: 'Đóng', en: 'Close', el: 'Κλείσιμο');

  String get blogTitle => pick(vi: 'BLOG PAPRIKA', en: 'PAPRIKA BLOG', el: 'BLOG PAPRIKA');
  String get blogSubtitle => pick(
        vi: 'Câu chuyện món Việt, tin nhà hàng và ưu đãi theo mùa tại Patras.',
        en: 'Vietnamese food stories, restaurant news and seasonal offers in Patras.',
        el: 'Ιστορίες βιετναμέζικης κουζίνας, νέα του εστιατορίου και εποχικές προσφορές στην Πάτρα.',
      );
  String get blogLoadError => pick(vi: 'Không tải được blog.', en: 'Could not load blog.', el: 'Δεν ήταν δυνατή η φόρτωση του blog.');
  String get featured => pick(vi: 'Nổi bật', en: 'Featured', el: 'Προτεινόμενο');
  String get readMore => pick(vi: 'Đọc tiếp', en: 'Read more', el: 'Διαβάστε περισσότερα');
  String get emptyBlog => pick(vi: 'Chưa có bài viết.', en: 'No posts yet.', el: 'Δεν υπάρχουν άρθρα ακόμα.');
  String get postLoadError => pick(vi: 'Không tải được bài viết', en: 'Could not load post', el: 'Δεν ήταν δυνατή η φόρτωση του άρθρου');
  String get relatedPosts => pick(vi: 'Bài viết liên quan', en: 'Related posts', el: 'Σχετικά άρθρα');

  String get galleryTitle => pick(vi: 'KHÔNG GIAN', en: 'SPACE', el: 'ΧΩΡΟΣ');
  String get gallerySubtitle => pick(
        vi: 'Những góc nhỏ tại Paprika Patras, từ bàn ăn đến bếp và các khoảnh khắc trong ngày.',
        en: 'Corners of Paprika Patras, from dining tables to the kitchen and everyday moments.',
        el: 'Γωνιές του Paprika Patras, από τα τραπέζια μέχρι την κουζίνα και τις στιγμές της ημέρας.',
      );
  String get galleryLoadError => pick(vi: 'Không tải được thư viện ảnh.', en: 'Could not load gallery.', el: 'Δεν ήταν δυνατή η φόρτωση της συλλογής.');
  String get all => pick(vi: 'Tất cả', en: 'All', el: 'Όλα');
  String get sharedImages => pick(vi: 'Ảnh chung', en: 'Shared photos', el: 'Κοινές φωτογραφίες');
  String get emptyGallery => pick(vi: 'Chưa có ảnh để hiển thị.', en: 'No photos to show yet.', el: 'Δεν υπάρχουν φωτογραφίες ακόμα.');

  String get pagesTitle => pick(vi: 'THÔNG TIN', en: 'INFO', el: 'ΠΛΗΡΟΦΟΡΙΕΣ');
  String get pagesSubtitle => pick(
        vi: 'Các trang nội dung được quản lý từ CMS Paprika.',
        en: 'Content pages managed from the Paprika CMS.',
        el: 'Σελίδες περιεχομένου από το Paprika CMS.',
      );
  String get pagesLoadError => pick(vi: 'Không tải được danh sách trang.', en: 'Could not load pages.', el: 'Δεν ήταν δυνατή η φόρτωση των σελίδων.');
  String get pageLoadError => pick(vi: 'Không tải được trang', en: 'Could not load page', el: 'Δεν ήταν δυνατή η φόρτωση της σελίδας');
  String get viewPage => pick(vi: 'Xem trang', en: 'View page', el: 'Προβολή σελίδας');
  String get emptyPages => pick(vi: 'Chưa có trang nội dung.', en: 'No content pages yet.', el: 'Δεν υπάρχουν σελίδες ακόμα.');

  String get offersTitle => pick(vi: 'ƯU ĐÃI', en: 'OFFERS', el: 'ΠΡΟΣΦΟΡΕΣ');
  String get offersSubtitle => pick(
        vi: 'Chọn mã phù hợp và áp dụng ngay khi checkout.',
        en: 'Choose a code and apply it at checkout.',
        el: 'Επιλέξτε έναν κωδικό και χρησιμοποιήστε τον στο checkout.',
      );
  String get offersLoadError => pick(vi: 'Không tải được danh sách ưu đãi.', en: 'Could not load offers.', el: 'Δεν ήταν δυνατή η φόρτωση των προσφορών.');
  String get suggested => pick(vi: 'Gợi ý', en: 'Suggested', el: 'Προτεινόμενο');
  String get emptyOffers => pick(vi: 'Chưa có ưu đãi công khai.', en: 'No public offers yet.', el: 'Δεν υπάρχουν δημόσιες προσφορές ακόμα.');
  String minOrder(String amount) => pick(vi: 'Đơn tối thiểu $amount', en: 'Minimum order $amount', el: 'Ελάχιστη παραγγελία $amount');
  String get noMinOrder => pick(vi: 'Không yêu cầu đơn tối thiểu', en: 'No minimum order', el: 'Χωρίς ελάχιστη παραγγελία');
  String get allBranches => pick(vi: 'Áp dụng mọi chi nhánh', en: 'All branches', el: 'Όλα τα καταστήματα');
  String expires(String date) => pick(vi: 'Hết hạn $date', en: 'Expires $date', el: 'Λήγει $date');
  String copiedCode(String code) => pick(vi: 'Đã sao chép mã $code.', en: 'Copied code $code.', el: 'Ο κωδικός $code αντιγράφηκε.');
  String get copy => pick(vi: 'Sao chép', en: 'Copy', el: 'Αντιγραφή');
  String get useCode => pick(vi: 'Dùng mã', en: 'Use code', el: 'Χρήση κωδικού');

  String get searchTitle => pick(vi: 'TÌM KIẾM', en: 'SEARCH', el: 'ΑΝΑΖΗΤΗΣΗ');
  String get searchHint => pick(vi: 'Tìm món, chi nhánh, mã ưu đãi...', en: 'Search dishes, branches, offer codes...', el: 'Αναζήτηση πιάτων, καταστημάτων, κωδικών...');
  String get searchPrompt => pick(vi: 'Nhập ít nhất 2 ký tự để tìm món ăn, chi nhánh và ưu đãi.', en: 'Enter at least 2 characters to search dishes, branches and offers.', el: 'Πληκτρολογήστε τουλάχιστον 2 χαρακτήρες για πιάτα, καταστήματα και προσφορές.');
  String get dishes => pick(vi: 'Món ăn', en: 'Dishes', el: 'Πιάτα');
  String get branches => pick(vi: 'Chi nhánh', en: 'Branches', el: 'Καταστήματα');
  String get dishSearchError => pick(vi: 'Không tìm được món.', en: 'Could not search dishes.', el: 'Δεν ήταν δυνατή η αναζήτηση πιάτων.');
  String get branchLoadError => pick(vi: 'Không tải được chi nhánh.', en: 'Could not load branches.', el: 'Δεν ήταν δυνατή η φόρτωση καταστημάτων.');
  String get offerLoadError => pick(vi: 'Không tải được ưu đãi.', en: 'Could not load offers.', el: 'Δεν ήταν δυνατή η φόρτωση προσφορών.');
  String get noMatchingDishes => pick(vi: 'Không có món phù hợp.', en: 'No matching dishes.', el: 'Δεν βρέθηκαν πιάτα.');
  String get noMatchingBranches => pick(vi: 'Không có chi nhánh phù hợp.', en: 'No matching branches.', el: 'Δεν βρέθηκαν καταστήματα.');
  String get noMatchingOffers => pick(vi: 'Không có ưu đãi phù hợp.', en: 'No matching offers.', el: 'Δεν βρέθηκαν προσφορές.');
  String searching(String title) => pick(vi: 'Đang tìm $title...', en: 'Searching $title...', el: 'Αναζήτηση $title...');

  String get orderLookupTitle => pick(vi: 'TRA CỨU ĐƠN HÀNG', en: 'ORDER LOOKUP', el: 'ΑΝΑΖΗΤΗΣΗ ΠΑΡΑΓΓΕΛΙΑΣ');
  String get orderLookupSubtitle => pick(vi: 'Nhập mã đơn, email hoặc số điện thoại để xem trạng thái và chi tiết món.', en: 'Enter an order code, email or phone number to view status and item details.', el: 'Πληκτρολογήστε κωδικό παραγγελίας, email ή τηλέφωνο για κατάσταση και λεπτομέρειες.');
  String get orderLookupRequired => pick(vi: 'Vui lòng nhập mã đơn, email hoặc số điện thoại.', en: 'Please enter an order code, email or phone number.', el: 'Πληκτρολογήστε κωδικό παραγγελίας, email ή τηλέφωνο.');
  String get orderLookupHint => pick(vi: 'VD: DH2609ABCDE, email hoặc số điện thoại', en: 'E.g. DH2609ABCDE, email or phone number', el: 'Π.χ. DH2609ABCDE, email ή τηλέφωνο');
  String get orderLookupLoading => pick(vi: 'Đang tra cứu...', en: 'Looking up...', el: 'Αναζήτηση...');
  String get orderLookupSubmit => pick(vi: 'TRA CỨU', en: 'LOOK UP', el: 'ΑΝΑΖΗΤΗΣΗ');
  String get orderLookupHintText => pick(vi: 'Bạn có thể tra cứu bằng mã đơn được hiển thị sau checkout.', en: 'You can look up using the order code shown after checkout.', el: 'Μπορείτε να αναζητήσετε με τον κωδικό που εμφανίζεται μετά το checkout.');
  String get noOrdersFound => pick(vi: 'Không tìm thấy đơn hàng phù hợp.', en: 'No matching orders found.', el: 'Δεν βρέθηκαν αντίστοιχες παραγγελίες.');
  String get orderDetailLoadError => pick(vi: 'Không tải được thông tin đơn hàng.', en: 'Could not load order details.', el: 'Δεν ήταν δυνατή η φόρτωση της παραγγελίας.');
  String get lookupAnotherOrder => pick(vi: 'Tra cứu đơn khác', en: 'Look up another order', el: 'Αναζήτηση άλλης παραγγελίας');
  String get orderTracking => pick(vi: 'Theo dõi trạng thái', en: 'Order tracking', el: 'Παρακολούθηση κατάστασης');
  String get orderedItems => pick(vi: 'Món đã đặt', en: 'Ordered items', el: 'Πιάτα παραγγελίας');
  String get orderTotal => pick(vi: 'Tổng đơn', en: 'Order total', el: 'Σύνολο παραγγελίας');
  String get subtotal => pick(vi: 'Tạm tính', en: 'Subtotal', el: 'Μερικό σύνολο');
  String get shippingFee => pick(vi: 'Phí giao hàng', en: 'Delivery fee', el: 'Κόστος παράδοσης');
  String get discount => pick(vi: 'Giảm giá', en: 'Discount', el: 'Έκπτωση');
  String get grandTotal => pick(vi: 'Tổng cộng', en: 'Total', el: 'Σύνολο');

  String get allergenTitle => pick(vi: 'Quản lý dị ứng', en: 'Allergen settings', el: 'Ρυθμίσεις αλλεργιογόνων');
  String get save => pick(vi: 'Lưu', en: 'Save', el: 'Αποθήκευση');
  String get allergenIntroTitle => pick(vi: 'Chọn các loại thực phẩm bạn dị ứng', en: 'Select foods you are allergic to', el: 'Επιλέξτε τροφές στις οποίες είστε αλλεργικοί');
  String get allergenIntroBody => pick(vi: 'Chúng tôi sẽ tự động cảnh báo khi món ăn có chứa các chất gây dị ứng bạn đã chọn.', en: 'We will automatically warn you when a dish contains selected allergens.', el: 'Θα σας προειδοποιούμε όταν ένα πιάτο περιέχει επιλεγμένα αλλεργιογόνα.');
  String get allergensCleared => pick(vi: 'Đã xoá danh sách dị ứng.', en: 'Allergen list cleared.', el: 'Η λίστα αλλεργιογόνων διαγράφηκε.');
  String allergensSaved(int count) => pick(vi: 'Đã lưu $count chất dị ứng.', en: 'Saved $count allergens.', el: 'Αποθηκεύτηκαν $count αλλεργιογόνα.');
  String allergensSelected(int count) => pick(vi: 'Đã chọn $count loại dị ứng', en: '$count allergens selected', el: 'Επιλέχθηκαν $count αλλεργιογόνα');

  String allergenLabel(String key) {
    return switch (key) {
      'gluten' => pick(vi: 'Gluten', en: 'Gluten', el: 'Γλουτένη'),
      'dairy' => pick(vi: 'Sữa', en: 'Dairy', el: 'Γαλακτοκομικά'),
      'egg' => pick(vi: 'Trứng', en: 'Egg', el: 'Αυγό'),
      'soy' => pick(vi: 'Đậu nành', en: 'Soy', el: 'Σόγια'),
      'sesame' => pick(vi: 'Mè', en: 'Sesame', el: 'Σουσάμι'),
      'mustard' => pick(vi: 'Mù tạt', en: 'Mustard', el: 'Μουστάρδα'),
      'seafood' => pick(vi: 'Hải sản', en: 'Seafood', el: 'Θαλασσινά'),
      'fish' => pick(vi: 'Cá', en: 'Fish', el: 'Ψάρι'),
      'peanut' => pick(vi: 'Đậu phộng', en: 'Peanut', el: 'Φιστίκι'),
      'tree_nuts' => pick(vi: 'Hạt cây', en: 'Tree nuts', el: 'Ξηροί καρποί'),
      'sulphites' => pick(vi: 'Lưu huỳnh', en: 'Sulphites', el: 'Θειώδη'),
      'celery' => pick(vi: 'Cần tây', en: 'Celery', el: 'Σέλινο'),
      'molluscs' => pick(vi: 'Động vật thân mềm', en: 'Molluscs', el: 'Μαλάκια'),
      'lupin' => pick(vi: 'Lupin', en: 'Lupin', el: 'Λούπινο'),
      _ => key,
    };
  }
}
