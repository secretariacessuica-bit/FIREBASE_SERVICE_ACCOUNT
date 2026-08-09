import 'package:equatable/equatable.dart';
import '../../domain/envelope.dart';

abstract class ServiceClosingEvent extends Equatable {
  @override
  List<Object?> get props => [];
}

class InitializeClosingContextEvent extends ServiceClosingEvent {
  final DateTime date;
  final String mainTreasurer;
  final String coTreasurer;
  InitializeClosingContextEvent(this.date, this.mainTreasurer, this.coTreasurer);
}

class LoadMembersEvent extends ServiceClosingEvent {}

class RestoreDraftEvent extends ServiceClosingEvent {
  final ServiceClosingState draftState;
  RestoreDraftEvent(this.draftState);
}

class AddEnvelopeEvent extends ServiceClosingEvent {
  final Envelope envelope;
  AddEnvelopeEvent(this.envelope);
}

class AnonymousEntry extends Equatable {
  final String id;
  final EnvelopeType type;
  final int amount;

  const AnonymousEntry({required this.id, required this.type, required this.amount});

  @override
  List<Object?> get props => [id, type, amount];

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type.name,
      'amount': amount,
    };
  }

  factory AnonymousEntry.fromJson(Map<String, dynamic> json) {
    return AnonymousEntry(
      id: json['id'],
      type: EnvelopeType.values.firstWhere((e) => e.name == json['type']),
      amount: json['amount'],
    );
  }
}

class RemoveEnvelopeEvent extends ServiceClosingEvent {
  final String id;
  RemoveEnvelopeEvent(this.id);
}

class AddAnonymousOfferingEvent extends ServiceClosingEvent {
  final AnonymousEntry entry;
  AddAnonymousOfferingEvent(this.entry);
}

class UndoAnonymousOfferingEvent extends ServiceClosingEvent {
  final String id;
  UndoAnonymousOfferingEvent(this.id);
}

class SetPhysicalTotalEvent extends ServiceClosingEvent {
  final int physicalTotal;
  SetPhysicalTotalEvent(this.physicalTotal);
}

class SubmitClosingEvent extends ServiceClosingEvent {
  final String? coTreasurer;
  SubmitClosingEvent({this.coTreasurer});

  @override
  List<Object?> get props => [coTreasurer];
}

class AddLocalMemberEvent extends ServiceClosingEvent {
  final String name;
  AddLocalMemberEvent(this.name);

  @override
  List<Object?> get props => [name];
}

class UndoAddedEntryEvent extends ServiceClosingEvent {
  final String entryId;
  UndoAddedEntryEvent(this.entryId);

  @override
  List<Object?> get props => [entryId];
}

class ServiceClosingState extends Equatable {
  final DateTime? date;
  final String mainTreasurer;
  final String? coTreasurer;
  final List<Envelope> identifiedEntries;
  final List<AnonymousEntry> anonymousEntries;
  final int physicalTotal;
  final String? error;
  final List<String> knownMembers;
  final bool isSubmitting;
  final bool isSuccess;

  int get identifiedTotal => identifiedEntries.fold(0, (sum, item) => sum + item.amount);
  int get anonymousTotal => anonymousEntries.fold(0, (sum, item) => sum + item.amount);
  int get registeredTotal => identifiedTotal + anonymousTotal;
  int get difference => physicalTotal - registeredTotal;

  int identifiedTotalBy(EnvelopeType type) => identifiedEntries.where((e) => e.type == type).fold(0, (sum, item) => sum + item.amount);
  int anonymousTotalBy(EnvelopeType type) => anonymousEntries.where((e) => e.type == type).fold(0, (sum, item) => sum + item.amount);

  const ServiceClosingState({
    this.date,
    this.mainTreasurer = "Admilson",
    this.coTreasurer,
    this.identifiedEntries = const [],
    this.anonymousEntries = const [],
    this.physicalTotal = 0,
    this.error,
    this.knownMembers = const [],
    this.isSubmitting = false,
    this.isSuccess = false,
  });

  ServiceClosingState copyWith({
    DateTime? date,
    String? mainTreasurer,
    String? coTreasurer,
    List<Envelope>? identifiedEntries,
    List<AnonymousEntry>? anonymousEntries,
    int? physicalTotal,
    String? error,
    List<String>? knownMembers,
    bool? isSubmitting,
    bool? isSuccess,
  }) {
    return ServiceClosingState(
      date: date ?? this.date,
      mainTreasurer: mainTreasurer ?? this.mainTreasurer,
      coTreasurer: coTreasurer ?? this.coTreasurer,
      identifiedEntries: identifiedEntries ?? this.identifiedEntries,
      anonymousEntries: anonymousEntries ?? this.anonymousEntries,
      physicalTotal: physicalTotal ?? this.physicalTotal,
      error: error,
      knownMembers: knownMembers ?? this.knownMembers,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      isSuccess: isSuccess ?? this.isSuccess,
    );
  }

  @override
  List<Object?> get props => [
    date, mainTreasurer, coTreasurer, 
    identifiedEntries, anonymousEntries, physicalTotal, error, knownMembers,
    isSubmitting, isSuccess
  ];

  Map<String, dynamic> toJson() {
    return {
      'date': date?.toIso8601String(),
      'mainTreasurer': mainTreasurer,
      'coTreasurer': coTreasurer,
      'identifiedEntries': identifiedEntries.map((e) => e.toJson()).toList(),
      'anonymousEntries': anonymousEntries.map((e) => e.toJson()).toList(),
      'physicalTotal': physicalTotal,
      'error': error,
      'knownMembers': knownMembers,
      'isSubmitting': isSubmitting,
      'isSuccess': isSuccess,
    };
  }

  factory ServiceClosingState.fromJson(Map<String, dynamic> json) {
    return ServiceClosingState(
      date: json['date'] != null ? DateTime.parse(json['date']) : null,
      mainTreasurer: json['mainTreasurer'] ?? "Admilson",
      coTreasurer: json['coTreasurer'],
      identifiedEntries: (json['identifiedEntries'] as List<dynamic>?)
              ?.map((e) => Envelope.fromJson(e))
              .toList() ??
          const [],
      anonymousEntries: (json['anonymousEntries'] as List<dynamic>?)
              ?.map((e) => AnonymousEntry.fromJson(e))
              .toList() ??
          const [],
      physicalTotal: json['physicalTotal'] ?? 0,
      error: json['error'],
      knownMembers: (json['knownMembers'] as List<dynamic>?)
              ?.map((e) => e as String)
              .toList() ??
          const [],
      isSubmitting: json['isSubmitting'] ?? false,
      isSuccess: json['isSuccess'] ?? false,
    );
  }
}
