import 'envelope.dart';

class ServiceClosingSummary {
  final int id;
  final String serviceDate;
  final String mainTreasurer;
  final String coTreasurer;
  final double physicalTotal;

  ServiceClosingSummary({
    required this.id,
    required this.serviceDate,
    required this.mainTreasurer,
    required this.coTreasurer,
    required this.physicalTotal,
  });

  factory ServiceClosingSummary.fromJson(Map<String, dynamic> json) {
    return ServiceClosingSummary(
      id: json['id'],
      serviceDate: json['serviceDate'] ?? '-',
      mainTreasurer: json['mainTreasurer'] ?? '-',
      coTreasurer: json['coTreasurer'] ?? '-',
      physicalTotal: (json['physicalTotal'] ?? 0).toDouble(),
    );
  }
}

class ServiceClosingDetail {
  final int id;
  final String serviceDate;
  final String mainTreasurer;
  final String coTreasurer;
  
  final List<Envelope> identifiedEntries;
  
  final double unidentifiedDizimoTotal;
  final double unidentifiedOfertaTotal;
  final double unidentifiedVotoTotal;
  
  final double identifiedTotal;
  final double unidentifiedTotal;
  final double registeredTotal;
  final double physicalTotal;

  ServiceClosingDetail({
    required this.id,
    required this.serviceDate,
    required this.mainTreasurer,
    required this.coTreasurer,
    required this.identifiedEntries,
    required this.unidentifiedDizimoTotal,
    required this.unidentifiedOfertaTotal,
    required this.unidentifiedVotoTotal,
    required this.identifiedTotal,
    required this.unidentifiedTotal,
    required this.registeredTotal,
    required this.physicalTotal,
  });

  factory ServiceClosingDetail.fromJson(Map<String, dynamic> json) {
    var list = json['identifiedEntries'] as List<dynamic>? ?? [];
    List<Envelope> entries = list.map((e) {
      EnvelopeType type;
      switch (e['type']?.toString().toUpperCase()) {
        case 'OFERTA': type = EnvelopeType.oferta; break;
        case 'VOTO': type = EnvelopeType.voto; break;
        case 'DIZIMO':
        default: type = EnvelopeType.dizimo; break;
      }
      return Envelope(
        id: '', // Not used for history viewing
        amount: ((e['amount'] ?? 0) * 100).toInt(), 
        type: type,
        memberName: e['memberName'] ?? '',
      );
    }).toList().cast<Envelope>();

    return ServiceClosingDetail(
      id: json['id'],
      serviceDate: json['serviceDate'] ?? '-',
      mainTreasurer: json['mainTreasurer'] ?? '-',
      coTreasurer: json['coTreasurer'] ?? '-',
      identifiedEntries: entries,
      unidentifiedDizimoTotal: (json['unidentifiedDizimoTotal'] ?? 0).toDouble(),
      unidentifiedOfertaTotal: (json['unidentifiedOfertaTotal'] ?? 0).toDouble(),
      unidentifiedVotoTotal: (json['unidentifiedVotoTotal'] ?? 0).toDouble(),
      identifiedTotal: (json['identifiedTotal'] ?? 0).toDouble(),
      unidentifiedTotal: (json['unidentifiedTotal'] ?? 0).toDouble(),
      registeredTotal: (json['registeredTotal'] ?? 0).toDouble(),
      physicalTotal: (json['physicalTotal'] ?? 0).toDouble(),
    );
  }
}
