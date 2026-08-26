import 'package:equatable/equatable.dart';

enum OrderStatus { pending, confirmed, preparing, delivering, completed, cancelled }

extension OrderStatusX on OrderStatus {
  String get labelVi {
    switch (this) {
      case OrderStatus.pending:
        return 'Chờ xác nhận';
      case OrderStatus.confirmed:
        return 'Đã xác nhận';
      case OrderStatus.preparing:
        return 'Đang chuẩn bị';
      case OrderStatus.delivering:
        return 'Đang giao';
      case OrderStatus.completed:
        return 'Hoàn thành';
      case OrderStatus.cancelled:
        return 'Đã hủy';
    }
  }
}

class Order extends Equatable {
  final int id;
  final String orderCode;
  final List<OrderItem> items;
  final int subtotal;
  final int shippingFee;
  final int total;
  final OrderStatus status;
  final DateTime createdAt;
  final String? address;
  final String? phone;
  final String? note;

  const Order({
    required this.id,
    required this.orderCode,
    required this.items,
    required this.subtotal,
    required this.shippingFee,
    required this.total,
    required this.status,
    required this.createdAt,
    this.address,
    this.phone,
    this.note,
  });

  @override
  List<Object?> get props => [id, status];
}

class OrderItem extends Equatable {
  final int dishId;
  final String dishName;
  final String? dishImage;
  final int price;
  final int quantity;

  const OrderItem({
    required this.dishId,
    required this.dishName,
    this.dishImage,
    required this.price,
    required this.quantity,
  });

  int get totalPrice => price * quantity;

  @override
  List<Object?> get props => [dishId, quantity];
}