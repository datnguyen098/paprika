import 'package:equatable/equatable.dart';

class ReservationAvailabilityRequest extends Equatable {
  const ReservationAvailabilityRequest({
    required this.branchId,
    required this.reservationDate,
    required this.reservationTime,
    required this.guests,
  });

  final int branchId;
  final String reservationDate;
  final String reservationTime;
  final int guests;

  Map<String, dynamic> toQuery() => {
        'branch_id': branchId,
        'reservation_date': reservationDate,
        'reservation_time': reservationTime,
        'guests': guests,
      };

  @override
  List<Object?> get props => [branchId, reservationDate, reservationTime, guests];
}

class ReservationTableAvailability extends Equatable {
  const ReservationTableAvailability({
    required this.id,
    required this.name,
    required this.seats,
    required this.available,
    this.code,
    this.zone,
    this.status,
    this.reason,
  });

  final int id;
  final String name;
  final int seats;
  final bool available;
  final String? code;
  final String? zone;
  final String? status;
  final String? reason;

  factory ReservationTableAvailability.fromJson(Map<String, dynamic> json) {
    return ReservationTableAvailability(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String? ?? json['code'] as String? ?? '',
      seats: (json['seats'] as num?)?.toInt() ?? 0,
      available: json['available'] as bool? ?? false,
      code: json['code'] as String?,
      zone: json['zone'] as String?,
      status: json['status'] as String?,
      reason: json['reason'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, available, reason];
}

class ReservationAvailabilityResponse extends Equatable {
  const ReservationAvailabilityResponse({
    required this.available,
    required this.tables,
    this.bestTableId,
    this.message,
  });

  final bool available;
  final int? bestTableId;
  final String? message;
  final List<ReservationTableAvailability> tables;

  int get availableCount => tables.where((table) => table.available).length;

  factory ReservationAvailabilityResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    final rawTables = data['tables'] as List? ?? const [];
    return ReservationAvailabilityResponse(
      available: data['available'] as bool? ?? false,
      bestTableId: (data['best_table_id'] as num?)?.toInt(),
      message: json['message'] as String?,
      tables: rawTables
          .whereType<Map>()
          .map((item) => ReservationTableAvailability.fromJson(
                Map<String, dynamic>.from(item),
              ))
          .toList(),
    );
  }

  @override
  List<Object?> get props => [available, bestTableId, tables];
}

class CreateReservationRequest extends Equatable {
  const CreateReservationRequest({
    required this.name,
    required this.phone,
    required this.branchId,
    required this.reservationDate,
    required this.reservationTime,
    required this.guests,
    this.email,
    this.note,
  });

  final String name;
  final String phone;
  final int branchId;
  final String reservationDate;
  final String reservationTime;
  final int guests;
  final String? email;
  final String? note;

  Map<String, dynamic> toJson() {
    final out = <String, dynamic>{
      'name': name.trim(),
      'phone': phone.trim(),
      'branch_id': branchId,
      'reservation_date': reservationDate,
      'reservation_time': reservationTime,
      'guests': guests,
    };
    if (email != null && email!.trim().isNotEmpty) {
      out['email'] = email!.trim();
    }
    if (note != null && note!.trim().isNotEmpty) {
      out['note'] = note!.trim();
    }
    return out;
  }

  Map<String, String> validate() {
    final errors = <String, String>{};
    if (name.trim().isEmpty) errors['name'] = 'Vui lòng nhập họ tên.';
    final phoneRegex = RegExp(r'^[0-9+\-\s().]{8,20}$');
    if (phone.trim().isEmpty) {
      errors['phone'] = 'Vui lòng nhập số điện thoại.';
    } else if (!phoneRegex.hasMatch(phone.trim())) {
      errors['phone'] = 'Số điện thoại chưa đúng định dạng.';
    }
    if (email != null && email!.trim().isNotEmpty) {
      final emailRegex = RegExp(r'^[\w.\-+]+@[\w\-]+\.[\w\-.]+$');
      if (!emailRegex.hasMatch(email!.trim())) {
        errors['email'] = 'Email chưa đúng định dạng.';
      }
    }
    if (guests < 1 || guests > 40) {
      errors['guests'] = 'Số khách phải từ 1 đến 40.';
    }
    return errors;
  }

  @override
  List<Object?> get props => [
        name,
        phone,
        branchId,
        reservationDate,
        reservationTime,
        guests,
        email,
        note,
      ];
}

class ReservationResponse extends Equatable {
  const ReservationResponse({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.reservationDate,
    required this.reservationTime,
    required this.guests,
    this.name,
    this.phone,
    this.email,
    this.branchName,
    this.branchAddress,
    this.branchPhone,
    this.tableName,
    this.tableSeats,
    this.note,
  });

  final int id;
  final String? name;
  final String? phone;
  final String? email;
  final String status;
  final String statusLabel;
  final String reservationDate;
  final String reservationTime;
  final int guests;
  final String? branchName;
  final String? branchAddress;
  final String? branchPhone;
  final String? tableName;
  final int? tableSeats;
  final String? note;

  factory ReservationResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] is Map<String, dynamic>
        ? json['data'] as Map<String, dynamic>
        : json;
    final branch = data['branch'] as Map<String, dynamic>?;
    final table = data['table'] as Map<String, dynamic>?;
    return ReservationResponse(
      id: (data['id'] as num?)?.toInt() ?? 0,
      name: data['name'] as String?,
      phone: data['phone'] as String?,
      email: data['email'] as String?,
      status: data['status'] as String? ?? 'pending',
      statusLabel: data['status_label'] as String? ??
          _fallbackReservationStatus(data['status'] as String? ?? 'pending'),
      reservationDate: data['reservation_date'] as String? ?? '',
      reservationTime: data['reservation_time'] as String? ?? '',
      guests: (data['guests'] as num?)?.toInt() ?? 0,
      branchName: branch?['name'] as String?,
      branchAddress: branch?['address'] as String?,
      branchPhone: branch?['phone'] as String?,
      tableName: table?['name'] as String?,
      tableSeats: (table?['seats'] as num?)?.toInt(),
      note: data['note'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, status, reservationDate, reservationTime];
}

String _fallbackReservationStatus(String status) {
  return switch (status) {
    'pending' => 'Chờ gọi xác nhận',
    'confirmed' => 'Đã giữ bàn',
    'seated' => 'Khách đã ngồi',
    'completed' => 'Hoàn tất',
    'no_show' => 'Không đến',
    'cancelled' => 'Đã huỷ',
    _ => status,
  };
}
