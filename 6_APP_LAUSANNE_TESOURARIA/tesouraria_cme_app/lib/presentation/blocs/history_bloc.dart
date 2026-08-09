import 'package:flutter_bloc/flutter_bloc.dart';
import '../../services/fechamento_api_service.dart';
import '../../domain/service_closing_history_models.dart';

abstract class HistoryEvent {}

class LoadHistoryEvent extends HistoryEvent {}

class LoadClosingDetailEvent extends HistoryEvent {
  final int id;
  LoadClosingDetailEvent(this.id);
}

class DeleteClosingEvent extends HistoryEvent {
  final int id;
  DeleteClosingEvent(this.id);
}

abstract class HistoryState {}

class HistoryInitial extends HistoryState {}

class HistoryLoading extends HistoryState {}

class HistoryLoaded extends HistoryState {
  final List<ServiceClosingSummary> history;
  HistoryLoaded(this.history);
}

class HistoryDeleting extends HistoryState {}

class HistoryDeleteSuccess extends HistoryState {}

class HistoryDetailLoading extends HistoryState {}

class HistoryDetailLoaded extends HistoryState {
  final ServiceClosingDetail detail;
  HistoryDetailLoaded(this.detail);
}

class HistoryError extends HistoryState {
  final String message;
  final bool isUnauthorized;
  HistoryError(this.message, {this.isUnauthorized = false});
}

class HistoryBloc extends Bloc<HistoryEvent, HistoryState> {
  final FechamentoApiService _apiService;

  HistoryBloc(this._apiService) : super(HistoryInitial()) {
    on<LoadHistoryEvent>((event, emit) async {
      emit(HistoryLoading());
      try {
        final history = await _apiService.fetchHistorico();
        emit(HistoryLoaded(history));
      } catch (e) {
        if (e.toString().contains('UNAUTHORIZED')) {
          emit(HistoryError('Sessão expirada.', isUnauthorized: true));
        } else {
          emit(HistoryError('Falha ao carregar histórico: $e'));
        }
      }
    });

    on<LoadClosingDetailEvent>((event, emit) async {
      emit(HistoryDetailLoading());
      try {
        final detail = await _apiService.fetchClosingDetail(event.id);
        emit(HistoryDetailLoaded(detail));
      } catch (e) {
        if (e.toString().contains('UNAUTHORIZED')) {
          emit(HistoryError('Sessão expirada.', isUnauthorized: true));
        } else {
          emit(HistoryError('Falha ao carregar detalhes: $e'));
        }
      }
    });

    on<DeleteClosingEvent>((event, emit) async {
      emit(HistoryDeleting());
      try {
        await _apiService.deleteClosing(event.id);
        emit(HistoryDeleteSuccess());
      } catch (e) {
        if (e.toString().contains('UNAUTHORIZED')) {
          emit(HistoryError('Sessão expirada.', isUnauthorized: true));
        } else {
          emit(HistoryError('Falha ao excluir fechamento: $e'));
        }
      }
    });
  }
}
