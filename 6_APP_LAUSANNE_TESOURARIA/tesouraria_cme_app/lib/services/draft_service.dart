import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../presentation/blocs/service_closing_events_states.dart';

class DraftService {
  static const String _draftKey = 'draft_closing';

  Future<void> saveDraft(ServiceClosingState state) async {
    final prefs = await SharedPreferences.getInstance();
    final jsonStr = jsonEncode(state.toJson());
    await prefs.setString(_draftKey, jsonStr);
  }

  Future<ServiceClosingState?> loadDraft() async {
    final prefs = await SharedPreferences.getInstance();
    final jsonStr = prefs.getString(_draftKey);
    if (jsonStr == null) return null;

    try {
      final data = jsonDecode(jsonStr);
      return ServiceClosingState.fromJson(data);
    } catch (e) {
      // Se houver erro de parsing (ex: mudança no modelo), ignoramos o draft
      return null;
    }
  }

  Future<void> clearDraft() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_draftKey);
  }
}
