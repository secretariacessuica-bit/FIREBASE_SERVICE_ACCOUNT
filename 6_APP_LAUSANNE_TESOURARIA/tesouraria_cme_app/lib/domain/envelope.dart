import 'package:equatable/equatable.dart';

enum EnvelopeType { dizimo, oferta, voto }

class Envelope extends Equatable {
  final String id;
  final String memberName;
  final EnvelopeType type;
  final int amount;

  const Envelope({
    required this.id,
    required this.memberName,
    required this.type,
    required this.amount,
  });

  @override
  List<Object?> get props => [id, memberName, type, amount];

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'memberName': memberName,
      'type': type.name,
      'amount': amount,
    };
  }

  factory Envelope.fromJson(Map<String, dynamic> json) {
    return Envelope(
      id: json['id'],
      memberName: json['memberName'],
      type: EnvelopeType.values.firstWhere((e) => e.name == json['type']),
      amount: json['amount'],
    );
  }
}
